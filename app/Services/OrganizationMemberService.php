<?php

namespace App\Services;

use App\Enums\UserAccountStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\Identity\UserInvitationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationMemberService
{
    public function __construct(
        protected OrganizationRoleService $roleService,
        protected UserInvitationService $invitations,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{name: string, email: string, role: string, send_invitation?: bool, notify?: bool}  $data
     */
    public function addMember(Organization $organization, array $data, ?User $actor = null): User
    {
        $email = strtolower(trim($data['email']));
        $actor ??= auth()->user();

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser && $organization->users()->where('users.id', $existingUser->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('This user is already a member of the organization.'),
            ]);
        }

        if ($existingUser) {
            return DB::transaction(function () use ($existingUser, $data, $organization, $actor) {
                $existingUser->update(['name' => $data['name']]);
                $organization->addMember($existingUser, $data['role']);

                $sendInvitation = (bool) ($data['send_invitation'] ?? false);
                if ($sendInvitation && $actor instanceof User) {
                    $this->invitations->invite($existingUser, $organization, $actor, [
                        'send_email' => ($data['notify'] ?? true) === true,
                    ]);
                }

                if ($actor instanceof User) {
                    $this->auditLogger->log($existingUser, 'team_member_added', [
                        'organization_id' => $organization->id,
                        'role' => $data['role'],
                        'existing_user' => true,
                    ], $actor);
                }

                return $existingUser;
            });
        }

        return DB::transaction(function () use ($data, $organization, $email, $actor) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'account_status' => UserAccountStatus::PendingInvitation,
                'portal_access_enabled' => true,
                'email_verified_at' => null,
                'password_changed_at' => null,
            ]);

            $organization->addMember($user, $data['role']);

            $sendInvitation = (bool) ($data['send_invitation'] ?? true);
            if ($sendInvitation && $actor instanceof User) {
                $this->invitations->invite($user, $organization, $actor, [
                    'send_email' => ($data['notify'] ?? true) === true,
                ]);
            }

            if ($actor instanceof User) {
                $this->auditLogger->log($user, 'team_member_added', [
                    'organization_id' => $organization->id,
                    'role' => $data['role'],
                    'invitation_sent' => $sendInvitation,
                ], $actor);
            }

            return $user;
        });
    }

    public function updateMemberRole(Organization $organization, User $member, string $roleSlug): void
    {
        if (! $member->belongsToOrganization($organization)) {
            abort(404);
        }

        if ($roleSlug === 'organization-owner') {
            throw ValidationException::withMessages([
                'role' => __('Organization Owner cannot be assigned from here.'),
            ]);
        }

        if ($member->isOwnerOf($organization) && $organization->owners()->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => __('Cannot change the role of the only organization owner.'),
            ]);
        }

        $this->roleService->assignRole($member, $organization, $roleSlug);
    }

    public function removeMember(Organization $organization, User $member): void
    {
        if (! $member->belongsToOrganization($organization)) {
            abort(404);
        }

        if ($member->isOwnerOf($organization) && $organization->owners()->count() <= 1) {
            throw ValidationException::withMessages([
                'member' => __('Cannot remove the only organization owner.'),
            ]);
        }

        DB::table('organization_user')
            ->where('organization_id', $organization->id)
            ->where('user_id', $member->id)
            ->delete();
    }

    /**
     * @return array<int, string>
     */
    public static function assignableRoleSlugs(): array
    {
        return array_values(array_filter(
            array_keys(config('rbac.roles', [])),
            fn (string $slug) => $slug !== 'organization-owner'
        ));
    }
}
