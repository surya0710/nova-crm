<?php

namespace App\Services\Platform;

use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformImpersonationService
{
    public const SESSION_KEY = 'platform_impersonation';

    public function __construct(protected PlatformAuditService $audit) {}

    public function canImpersonate(PlatformUser $actor, Organization $organization): bool
    {
        if (! $actor->hasPermission('platform.impersonate')) {
            return false;
        }

        if ($organization->isSuspended() || $organization->isArchived()) {
            return false;
        }

        $owner = $organization->primaryOwner();

        if (! $owner) {
            return false;
        }

        if ($actor->role === 'platform-support') {
            $isPlatformOwnerEmail = PlatformUser::query()
                ->where('role', 'platform-owner')
                ->where('email', $owner->email)
                ->exists();

            if ($isPlatformOwnerEmail) {
                return false;
            }
        }

        return true;
    }

    public function createToken(PlatformUser $actor, Organization $organization): string
    {
        if (! $this->canImpersonate($actor, $organization)) {
            throw ValidationException::withMessages([
                'impersonation' => __('You are not allowed to impersonate this organization.'),
            ]);
        }

        $targetUser = $organization->primaryOwner();

        if (! $targetUser) {
            throw ValidationException::withMessages([
                'impersonation' => __('This organization has no users to impersonate.'),
            ]);
        }

        $token = Str::random(64);

        Cache::put($this->cacheKey($token), [
            'platform_user_id' => $actor->id,
            'organization_id' => $organization->id,
            'target_user_id' => $targetUser->id,
        ], now()->addMinutes(5));

        $this->audit->log(
            'impersonation.started',
            $actor,
            $organization,
            [
                'impersonated_user_id' => $targetUser->id,
                'impersonated_user_email' => $targetUser->email,
            ],
            "Impersonating {$organization->name}",
        );

        return $token;
    }

    public function acceptToken(string $token, Request $request): User
    {
        $payload = Cache::pull($this->cacheKey($token));

        if (! $payload) {
            throw ValidationException::withMessages([
                'impersonation' => __('Impersonation token is invalid or expired.'),
            ]);
        }

        $targetUser = User::query()->findOrFail($payload['target_user_id']);
        $organization = Organization::query()->findOrFail($payload['organization_id']);
        $actor = PlatformUser::query()->find($payload['platform_user_id']);

        $request->session()->put(self::SESSION_KEY, [
            'platform_user_id' => $payload['platform_user_id'],
            'organization_id' => $organization->id,
            'started_at' => now()->toIso8601String(),
        ]);

        Auth::guard('web')->login($targetUser);
        $request->session()->put('current_organization_id', $organization->id);

        return $targetUser;
    }

    public function stop(Request $request): void
    {
        $data = $request->session()->get(self::SESSION_KEY);

        if ($data) {
            $actor = PlatformUser::find($data['platform_user_id'] ?? null);
            $organization = Organization::find($data['organization_id'] ?? null);

            if ($actor) {
                $this->audit->log(
                    'impersonation.ended',
                    $actor,
                    $organization,
                    $data,
                    $organization ? "Stopped impersonating {$organization->name}" : 'Stopped impersonation',
                    $request,
                );
            }
        }

        Auth::guard('web')->logout();
        $request->session()->forget(['current_organization_id', self::SESSION_KEY]);
    }

    public function isActive(Request $request): bool
    {
        return $request->session()->has(self::SESSION_KEY);
    }

    public function context(Request $request): ?array
    {
        return $request->session()->get(self::SESSION_KEY);
    }

    protected function cacheKey(string $token): string
    {
        return "platform:impersonation:{$token}";
    }
}
