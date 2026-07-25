<?php

namespace App\Services\Rbac;

use App\Events\Rbac\UserRoleAssigned;
use App\Events\Rbac\UserRoleRemoved;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\RbacNotification;
use App\Services\AuditLogger;
use App\Services\OrganizationRoleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserRoleService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected AuthorizationService $authorizationService,
        protected OrganizationRoleService $organizationRoleService,
    ) {}

    public function assign(User $user, Organization $organization, Role $role, ?User $actor = null, bool $primary = false): UserRole
    {
        if (! $user->belongsToOrganization($organization)) {
            throw ValidationException::withMessages([
                'user' => __('User is not a member of this organization.'),
            ]);
        }

        if ($role->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'role' => __('Role does not belong to this organization.'),
            ]);
        }

        $userRole = UserRole::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'role_id' => $role->id,
                'user_id' => $user->id,
            ],
            [
                'assigned_by' => $actor?->id,
                'assigned_at' => now(),
            ]
        );

        if ($primary) {
            $this->organizationRoleService->assignRole($user, $organization, $role);
        }

        event(new UserRoleAssigned($user, $role, $actor));
        $this->auditLogger->log($userRole, 'rbac.user_role.assigned', [
            'user_id' => $user->id,
            'role_slug' => $role->slug,
            'primary' => $primary,
        ], $actor);

        $user->notify(new RbacNotification(
            title: __('Role assigned'),
            message: __('You were assigned the :role role.', ['role' => $role->name]),
            actionUrl: route('rbac.user-roles.index'),
            organizationId: $organization->id,
            type: 'role_assigned',
        ));

        $this->authorizationService->forgetUserCache($user, $organization);

        return $userRole;
    }

    public function remove(User $user, Organization $organization, Role $role, ?User $actor = null): void
    {
        $deleted = UserRole::query()
            ->where('organization_id', $organization->id)
            ->where('role_id', $role->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            event(new UserRoleRemoved($user, $role, $actor));
            $this->auditLogger->log($user, 'rbac.user_role.removed', [
                'role_slug' => $role->slug,
            ], $actor);

            $user->notify(new RbacNotification(
                title: __('Role removed'),
                message: __('The :role role was removed from your account.', ['role' => $role->name]),
                organizationId: $organization->id,
                type: 'role_removed',
            ));

            $this->authorizationService->forgetUserCache($user, $organization);
        }
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    public function sync(User $user, Organization $organization, array $roleIds, ?User $actor = null, ?int $primaryRoleId = null): void
    {
        DB::transaction(function () use ($user, $organization, $roleIds, $actor, $primaryRoleId) {
            $existing = UserRole::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->pluck('role_id')
                ->all();

            foreach (array_diff($existing, $roleIds) as $roleId) {
                $role = Role::query()->find($roleId);
                if ($role) {
                    $this->remove($user, $organization, $role, $actor);
                }
            }

            foreach (array_diff($roleIds, $existing) as $roleId) {
                $role = Role::query()->findOrFail($roleId);
                $this->assign($user, $organization, $role, $actor, $roleId === $primaryRoleId);
            }

            if ($primaryRoleId) {
                $primaryRole = Role::query()->findOrFail($primaryRoleId);
                $this->organizationRoleService->assignRole($user, $organization, $primaryRole);
            }
        });
    }

    public function rolesForUser(User $user, Organization $organization): Collection
    {
        $roles = UserRole::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->with('role')
            ->get()
            ->pluck('role');

        $primaryRole = $user->getRoleInOrganization($organization);
        if ($primaryRole && ! $roles->contains('id', $primaryRole->id)) {
            $roles->prepend($primaryRole);
        }

        return $roles->unique('id')->values();
    }

    public function effectivePermissions(User $user, Organization $organization): Collection
    {
        return $this->authorizationService->effectivePermissions($user, $organization);
    }
}
