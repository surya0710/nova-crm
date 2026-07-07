<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OrganizationMemberService
{
    public function __construct(protected OrganizationRoleService $roleService) {}

    /**
     * @param  array{name: string, email: string, role: string, password?: string|null}  $data
     */
    public function addMember(Organization $organization, array $data): User
    {
        $email = strtolower(trim($data['email']));

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser && $organization->users()->where('users.id', $existingUser->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('This user is already a member of the organization.'),
            ]);
        }

        if ($existingUser) {
            $existingUser->update(['name' => $data['name']]);
            $user = $existingUser;
        } else {
            if (empty($data['password'])) {
                throw ValidationException::withMessages([
                    'password' => __('A password is required when creating a new user account.'),
                ]);
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make($data['password']),
            ]);
        }

        $organization->addMember($user, $data['role']);

        return $user;
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
