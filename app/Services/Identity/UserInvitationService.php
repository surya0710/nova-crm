<?php

namespace App\Services\Identity;

use App\Enums\UserAccountStatus;
use App\Mail\UserInvitationMail;
use App\Mail\UserWelcomeMail;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\OrganizationMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserInvitationService
{
    public function __construct(
        protected OrganizationMailer $mailer,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    /**
     * @param  array{send_email?: bool}  $options
     */
    public function invite(User $user, Organization $organization, User $actor, array $options = []): UserInvitation
    {
        $sendEmail = $options['send_email'] ?? true;
        $plainToken = Str::random(64);

        $invitation = DB::transaction(function () use ($user, $organization, $actor, $plainToken): UserInvitation {
            $this->revokePendingInvitations($user, $organization);

            $user->forceFill([
                'account_status' => UserAccountStatus::PendingInvitation,
            ])->save();

            $invitation = UserInvitation::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'invited_by' => $actor->id,
                'email' => $user->email,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addHours((int) config('identity.invitation_expiry_hours', 72)),
                'sent_at' => now(),
            ]);

            $this->auditLogger->log($user, 'user_invitation_sent', [
                'organization_id' => $organization->id,
                'invitation_id' => $invitation->id,
                'expires_at' => $invitation->expires_at?->toIso8601String(),
            ], $actor);

            return $invitation;
        });

        if ($sendEmail) {
            $this->sendInvitationEmail($user, $organization, $invitation, $plainToken);
        }

        // Store plaintext only in memory for the caller (tests / immediate email).
        $invitation->setAttribute('plain_token', $plainToken);

        return $invitation;
    }

    /**
     * @param  array{send_email?: bool}  $options
     */
    public function resend(User $user, Organization $organization, User $actor, array $options = []): UserInvitation
    {
        return $this->invite($user, $organization, $actor, $options);
    }

    public function accept(string $plainToken, string $password): User
    {
        $invitation = $this->findAcceptableByToken($plainToken);

        if (! $invitation) {
            throw ValidationException::withMessages([
                'token' => __('This invitation link is invalid or has expired.'),
            ]);
        }

        return DB::transaction(function () use ($invitation, $password, $plainToken): User {
            // Re-check under lock
            $invitation = UserInvitation::query()
                ->withoutGlobalScopes()
                ->whereKey($invitation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isAcceptable() || ! hash_equals($invitation->token_hash, hash('sha256', $plainToken))) {
                throw ValidationException::withMessages([
                    'token' => __('This invitation link is invalid or has expired.'),
                ]);
            }

            $user = User::query()->lockForUpdate()->findOrFail($invitation->user_id);
            $organization = Organization::query()->findOrFail($invitation->organization_id);

            $user->forceFill([
                'password' => $password,
                'account_status' => UserAccountStatus::Active,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'password_changed_at' => now(),
                'failed_login_attempts' => 0,
                'locked_at' => null,
            ])->save();

            $invitation->forceFill([
                'accepted_at' => now(),
            ])->save();

            $this->revokePendingInvitations($user, $organization, excludeId: $invitation->id);

            $this->auditLogger->log($user, 'user_invitation_accepted', [
                'organization_id' => $organization->id,
                'invitation_id' => $invitation->id,
            ], $user);

            $this->sendWelcome($user, $organization);

            return $user->fresh();
        });
    }

    public function findAcceptableByToken(string $plainToken): ?UserInvitation
    {
        if ($plainToken === '') {
            return null;
        }

        $invitation = UserInvitation::query()
            ->withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $invitation || ! $invitation->isAcceptable()) {
            return null;
        }

        return $invitation;
    }

    public function findLatestPending(User $user, Organization $organization): ?UserInvitation
    {
        return UserInvitation::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();
    }

    public function invitationStatus(User $user, Organization $organization): array
    {
        $latest = UserInvitation::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $display = $user->displayAccountStatus();

        return [
            'account_status' => $user->account_status?->value ?? UserAccountStatus::Active->value,
            'display_status' => $display,
            'display_label' => $user->displayAccountStatusLabel(),
            'invitation' => $latest ? [
                'id' => $latest->id,
                'sent_at' => $latest->sent_at?->toIso8601String(),
                'expires_at' => $latest->expires_at?->toIso8601String(),
                'accepted_at' => $latest->accepted_at?->toIso8601String(),
                'revoked_at' => $latest->revoked_at?->toIso8601String(),
                'is_pending' => $latest->isPending(),
                'is_expired' => $latest->isExpired(),
                'is_acceptable' => $latest->isAcceptable(),
            ] : null,
        ];
    }

    protected function revokePendingInvitations(User $user, Organization $organization, ?int $excludeId = null): void
    {
        UserInvitation::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->update(['revoked_at' => now()]);
    }

    protected function sendInvitationEmail(User $user, Organization $organization, UserInvitation $invitation, string $plainToken): void
    {
        try {
            $acceptUrl = $this->invitationAcceptUrl($plainToken);

            if ($this->mailer->isConfigured($organization)) {
                $this->mailer->send(
                    $organization,
                    $user->email,
                    new UserInvitationMail($user, $organization, $invitation, $acceptUrl)
                );
            } else {
                // Fall back to default mailer so local/dev still delivers.
                \Illuminate\Support\Facades\Mail::to($user->email)->send(
                    new UserInvitationMail($user, $organization, $invitation, $acceptUrl)
                );
            }
        } catch (\Throwable) {
            // Email failures must not roll back invitation creation.
        }
    }

    protected function invitationAcceptUrl(string $plainToken): string
    {
        if (\Illuminate\Support\Facades\Route::has('invitations.accept')) {
            return route('invitations.accept', ['token' => $plainToken]);
        }

        return url('/invitations/'.$plainToken);
    }

    protected function sendWelcome(User $user, Organization $organization): void
    {
        try {
            $loginUrl = route('login');
            if ($this->mailer->isConfigured($organization)) {
                $this->mailer->send($organization, $user->email, new UserWelcomeMail($user, $organization, $loginUrl));
            } else {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(
                    new UserWelcomeMail($user, $organization, $loginUrl)
                );
            }
        } catch (\Throwable) {
            // ignore
        }

        try {
            $this->notificationService->send(
                $organization->id,
                $user->id,
                __('Welcome to :org', ['org' => $organization->name]),
                __('Your account is active. You can sign in and access your workspace.'),
                '/ess'
            );
        } catch (\Throwable) {
            // ignore
        }
    }
}
