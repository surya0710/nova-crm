<?php

namespace App\Services\Identity;

use App\Enums\UserAccountStatus;
use App\Mail\PortalAccessEnabledMail;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\OrganizationMailer;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class UserAccountService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected OrganizationMailer $mailer,
        protected NotificationService $notificationService,
    ) {}

    public function displayStatus(User $user): string
    {
        return $user->displayAccountStatus();
    }

    public function displayStatusLabel(User $user): string
    {
        return $user->displayAccountStatusLabel();
    }

    public function enablePortal(User $user, Organization $organization, User $actor, bool $notify = true): User
    {
        $this->assertMember($user, $organization);

        $user->forceFill(['portal_access_enabled' => true])->save();

        $this->auditLogger->log($user, 'portal_access_enabled', [
            'organization_id' => $organization->id,
        ], $actor);

        if ($notify) {
            $this->notifyPortalEnabled($user, $organization);
        }

        return $user->fresh();
    }

    public function disablePortal(User $user, Organization $organization, User $actor): User
    {
        $this->assertMember($user, $organization);

        $user->forceFill(['portal_access_enabled' => false])->save();

        $this->auditLogger->log($user, 'portal_access_disabled', [
            'organization_id' => $organization->id,
        ], $actor);

        return $user->fresh();
    }

    public function lock(User $user, Organization $organization, User $actor): User
    {
        $this->assertMember($user, $organization);

        $user->forceFill([
            'account_status' => UserAccountStatus::Locked,
            'locked_at' => now(),
        ])->save();

        $this->auditLogger->log($user, 'user_account_locked', [
            'organization_id' => $organization->id,
        ], $actor);

        return $user->fresh();
    }

    public function unlock(User $user, Organization $organization, User $actor): User
    {
        $this->assertMember($user, $organization);

        $status = UserAccountStatus::Active;
        if ($user->email_verified_at === null && $user->password_changed_at === null) {
            $status = UserAccountStatus::PendingInvitation;
        }

        // If still pending invitation (never accepted), restore pending.
        $pendingInvite = app(UserInvitationService::class)->findLatestPending($user, $organization);
        if ($pendingInvite) {
            $status = UserAccountStatus::PendingInvitation;
        }

        $user->forceFill([
            'account_status' => $status,
            'locked_at' => null,
            'failed_login_attempts' => 0,
        ])->save();

        $this->auditLogger->log($user, 'user_account_unlocked', [
            'organization_id' => $organization->id,
            'restored_status' => $status->value,
        ], $actor);

        return $user->fresh();
    }

    public function disable(User $user, Organization $organization, User $actor): User
    {
        $this->assertMember($user, $organization);

        $user->forceFill([
            'account_status' => UserAccountStatus::Disabled,
            'disabled_at' => now(),
            'portal_access_enabled' => false,
        ])->save();

        $this->auditLogger->log($user, 'user_account_disabled', [
            'organization_id' => $organization->id,
        ], $actor);

        return $user->fresh();
    }

    public function enable(User $user, Organization $organization, User $actor): User
    {
        $this->assertMember($user, $organization);

        $user->forceFill([
            'account_status' => UserAccountStatus::Active,
            'disabled_at' => null,
        ])->save();

        $this->auditLogger->log($user, 'user_account_enabled', [
            'organization_id' => $organization->id,
        ], $actor);

        return $user->fresh();
    }

    public function recordSuccessfulLogin(User $user): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'login_count' => (int) $user->login_count + 1,
            'failed_login_attempts' => 0,
        ])->save();

        $this->auditLogger->log($user, 'user_login', [
            'login_count' => $user->login_count,
        ], $user);
    }

    public function recordFailedLogin(?User $user): void
    {
        if (! $user) {
            return;
        }

        $attempts = (int) $user->failed_login_attempts + 1;
        $threshold = (int) config('identity.failed_login_lock_threshold', 10);

        $attributes = ['failed_login_attempts' => $attempts];

        if ($threshold > 0 && $attempts >= $threshold && $user->account_status === UserAccountStatus::Active) {
            $attributes['account_status'] = UserAccountStatus::Locked;
            $attributes['locked_at'] = now();
        }

        $user->forceFill($attributes)->save();

        $this->auditLogger->log($user, 'user_login_failed', [
            'failed_login_attempts' => $attempts,
            'locked' => isset($attributes['locked_at']),
        ], $user);
    }

    public function recordLogout(User $user): void
    {
        $user->forceFill(['last_logout_at' => now()])->save();

        $this->auditLogger->log($user, 'user_logout', [], $user);
    }

    public function sendPasswordReset(User $user, Organization $organization, User $actor): string
    {
        $this->assertMember($user, $organization);

        if ($user->account_status === UserAccountStatus::Disabled) {
            throw ValidationException::withMessages([
                'user' => __('Cannot reset password for a disabled account.'),
            ]);
        }

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        $this->auditLogger->log($user, 'user_password_reset_sent', [
            'organization_id' => $organization->id,
        ], $actor);

        return $status;
    }

    public function loginActivity(User $user): array
    {
        return [
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'last_logout_at' => $user->last_logout_at?->toIso8601String(),
            'login_count' => (int) $user->login_count,
            'failed_login_attempts' => (int) $user->failed_login_attempts,
            'password_changed_at' => $user->password_changed_at?->toIso8601String(),
            'account_status' => $user->account_status?->value,
            'display_status' => $user->displayAccountStatus(),
            'display_label' => $user->displayAccountStatusLabel(),
            'portal_access_enabled' => (bool) $user->portal_access_enabled,
            'locked_at' => $user->locked_at?->toIso8601String(),
            'disabled_at' => $user->disabled_at?->toIso8601String(),
        ];
    }

    public function assertCanLogin(User $user): void
    {
        $status = $user->account_status ?? UserAccountStatus::Active;

        if ($status === UserAccountStatus::Disabled) {
            throw ValidationException::withMessages([
                'email' => __('This account has been disabled. Contact your administrator.'),
            ]);
        }

        if ($status === UserAccountStatus::Locked) {
            throw ValidationException::withMessages([
                'email' => __('This account has been locked. Contact your administrator.'),
            ]);
        }

        if ($status === UserAccountStatus::PendingInvitation) {
            throw ValidationException::withMessages([
                'email' => __('Please accept your invitation and set a password before signing in.'),
            ]);
        }

        if (! $status->canAuthenticate()) {
            throw ValidationException::withMessages([
                'email' => __('Unable to sign in with this account.'),
            ]);
        }
    }

    public function employeeForUser(User $user, Organization $organization): ?Employee
    {
        return Employee::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();
    }

    protected function assertMember(User $user, Organization $organization): void
    {
        if (! $user->belongsToOrganization($organization)) {
            abort(404);
        }
    }

    protected function notifyPortalEnabled(User $user, Organization $organization): void
    {
        $portalUrl = url('/ess');

        try {
            if ($this->mailer->isConfigured($organization)) {
                $this->mailer->send(
                    $organization,
                    $user->email,
                    new PortalAccessEnabledMail($user, $organization, $portalUrl)
                );
            } else {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(
                    new PortalAccessEnabledMail($user, $organization, $portalUrl)
                );
            }
        } catch (\Throwable) {
            // ignore
        }

        try {
            $this->notificationService->send(
                $organization->id,
                $user->id,
                __('Employee Workspace enabled'),
                __('Your Employee Workspace (self-service) access has been enabled.'),
                '/ess'
            );
        } catch (\Throwable) {
            // ignore
        }
    }
}
