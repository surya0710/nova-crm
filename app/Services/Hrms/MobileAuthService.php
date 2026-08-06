<?php

namespace App\Services\Hrms;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Identity\UserAccountService;
use App\Services\TenantContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Mobile auth orchestration on top of Sanctum + LoginRequest + Password Broker.
 * Does not introduce a second authentication system.
 */
class MobileAuthService
{
    public function __construct(
        protected UserAccountService $accounts,
        protected TenantContext $tenant,
        protected EssContext $essContext,
    ) {}

    /**
     * @param  array<string, mixed>  $devicePayload
     * @return array{user: User, access_token: string, refresh_token: string, access_expires_at: string|null, refresh_expires_at: string|null, device: UserDevice|null, organizations: list<array<string, mixed>>, employee: Employee|null}
     */
    public function login(LoginRequest $request, array $devicePayload = []): array
    {
        $request->ensureIsNotRateLimited();

        $email = Str::lower($request->string('email'));
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $this->accounts->assertCanLogin($user);
        }

        if (! Auth::guard('web')->validate($request->only('email', 'password'))) {
            \Illuminate\Support\Facades\RateLimiter::hit($request->throttleKey());
            $this->accounts->recordFailedLogin($user);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($request->throttleKey());

        /** @var User $user */
        $user = User::query()->where('email', $email)->firstOrFail();
        $this->accounts->recordSuccessfulLogin($user);

        $organization = $user->activeOrganizations()->first();
        if ($organization) {
            $this->tenant->set($organization);
        }

        $employee = $organization
            ? $this->accounts->employeeForUser($user, $organization)
            : null;

        $tokens = $this->issueTokenPair($user, $devicePayload['device_uuid'] ?? 'unknown');
        $device = null;

        if (! empty($devicePayload['device_uuid'])) {
            $device = $this->upsertDevice($user, $employee, $devicePayload, $request, $tokens);
        }

        return [
            'user' => $user,
            'access_token' => $tokens['access_plain'],
            'refresh_token' => $tokens['refresh_plain'],
            'access_expires_at' => $tokens['access_expires_at'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
            'device' => $device,
            'organizations' => $user->activeOrganizations()->get(['organizations.id', 'organizations.name', 'organizations.slug'])
                ->map(fn ($org) => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                ])->values()->all(),
            'employee' => $employee,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string, access_expires_at: string|null, refresh_expires_at: string|null}
     */
    public function refresh(string $refreshTokenPlain): array
    {
        $token = PersonalAccessToken::findToken($refreshTokenPlain);

        if (! $token || ! $token->can(config('hrms.mobile.refresh_token_ability'))) {
            throw ValidationException::withMessages([
                'refresh_token' => __('Invalid refresh token.'),
            ]);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            throw ValidationException::withMessages([
                'refresh_token' => __('Refresh token has expired.'),
            ]);
        }

        /** @var User $user */
        $user = $token->tokenable;
        $this->accounts->assertCanLogin($user);

        $deviceUuid = Str::after($token->name, 'hrms-mobile-refresh:');
        if ($deviceUuid === $token->name) {
            $deviceUuid = 'unknown';
        }

        $token->delete();

        // Revoke prior access tokens for this device name pair
        $user->tokens()
            ->where('name', 'hrms-mobile-access:'.$deviceUuid)
            ->delete();

        $tokens = $this->issueTokenPair($user, $deviceUuid);

        UserDevice::query()
            ->where('user_id', $user->id)
            ->where('device_uuid', $deviceUuid)
            ->update([
                'access_token_id' => $tokens['access_id'],
                'refresh_token_id' => $tokens['refresh_id'],
                'last_seen_at' => now(),
            ]);

        return [
            'access_token' => $tokens['access_plain'],
            'refresh_token' => $tokens['refresh_plain'],
            'access_expires_at' => $tokens['access_expires_at'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
        ];
    }

    public function logout(User $user, ?Request $request = null): void
    {
        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken) {
            $deviceUuid = Str::after($current->name, 'hrms-mobile-access:');
            if ($deviceUuid !== $current->name) {
                $user->tokens()
                    ->whereIn('name', [
                        'hrms-mobile-access:'.$deviceUuid,
                        'hrms-mobile-refresh:'.$deviceUuid,
                    ])
                    ->delete();

                UserDevice::query()
                    ->where('user_id', $user->id)
                    ->where('device_uuid', $deviceUuid)
                    ->update(['is_active' => false, 'access_token_id' => null, 'refresh_token_id' => null]);
            } else {
                $current->delete();
            }
        }

        $this->accounts->recordLogout($user);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The current password is incorrect.'),
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()
            ->where('name', 'like', 'hrms-mobile-%')
            ->delete();

        UserDevice::query()
            ->where('user_id', $user->id)
            ->update(['is_active' => false, 'access_token_id' => null, 'refresh_token_id' => null]);
    }

    public function sendForgotPasswordLink(string $email): string
    {
        $status = Password::broker()->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return $status;
    }

    /**
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $data
     */
    public function resetPassword(array $data): string
    {
        $status = Password::broker()->reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'password_changed_at' => now(),
                ])->save();

                $user->tokens()->where('name', 'like', 'hrms-mobile-%')->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return $status;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function registerDevice(User $user, array $payload, Request $request): UserDevice
    {
        $employee = $this->essContext->employeeFor($user);

        return $this->upsertDevice($user, $employee, $payload, $request, null);
    }

    public function deactivateDevice(User $user, UserDevice $device): void
    {
        abort_unless((int) $device->user_id === (int) $user->id, 404);

        if ($device->access_token_id) {
            PersonalAccessToken::query()->whereKey($device->access_token_id)->delete();
        }
        if ($device->refresh_token_id) {
            PersonalAccessToken::query()->whereKey($device->refresh_token_id)->delete();
        }

        $device->forceFill([
            'is_active' => false,
            'access_token_id' => null,
            'refresh_token_id' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $devicePayload
     * @param  array{access_id?: int, refresh_id?: int}|null  $tokens
     */
    protected function upsertDevice(
        User $user,
        ?Employee $employee,
        array $devicePayload,
        Request $request,
        ?array $tokens,
    ): UserDevice {
        $organizationId = $this->tenant->id()
            ?? $user->activeOrganizations()->value('organizations.id');

        $device = UserDevice::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_uuid' => $devicePayload['device_uuid'],
            ],
            [
                'organization_id' => $organizationId,
                'employee_id' => $employee?->id,
                'device_name' => $devicePayload['device_name'] ?? null,
                'platform' => $devicePayload['platform'] ?? null,
                'app_version' => $devicePayload['app_version'] ?? null,
                'push_token' => $devicePayload['push_token'] ?? null,
                'last_login_at' => now(),
                'last_seen_at' => now(),
                'last_ip' => $request->ip(),
                'is_active' => true,
                'access_token_id' => $tokens['access_id'] ?? null,
                'refresh_token_id' => $tokens['refresh_id'] ?? null,
            ]
        );

        return $device;
    }

    /**
     * @return array{access_plain: string, refresh_plain: string, access_id: int, refresh_id: int, access_expires_at: string|null, refresh_expires_at: string|null}
     */
    protected function issueTokenPair(User $user, string $deviceUuid): array
    {
        $accessAbility = config('hrms.mobile.access_token_ability', 'hrms-mobile');
        $refreshAbility = config('hrms.mobile.refresh_token_ability', 'hrms-mobile-refresh');
        $accessTtl = (int) config('hrms.mobile.access_token_ttl_minutes', 60);
        $refreshDays = (int) config('hrms.mobile.refresh_token_ttl_days', 30);

        $access = $user->createToken(
            'hrms-mobile-access:'.$deviceUuid,
            [$accessAbility],
            now()->addMinutes($accessTtl)
        );

        $refresh = $user->createToken(
            'hrms-mobile-refresh:'.$deviceUuid,
            [$refreshAbility],
            now()->addDays($refreshDays)
        );

        return [
            'access_plain' => $access->plainTextToken,
            'refresh_plain' => $refresh->plainTextToken,
            'access_id' => $access->accessToken->id,
            'refresh_id' => $refresh->accessToken->id,
            'access_expires_at' => $access->accessToken->expires_at?->toIso8601String(),
            'refresh_expires_at' => $refresh->accessToken->expires_at?->toIso8601String(),
        ];
    }
}
