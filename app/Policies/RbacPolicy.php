<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionTemplate;
use App\Models\Role;
use App\Models\User;
use App\Services\Rbac\AuthorizationService;
use App\Services\TenantContext;

class RbacPolicy
{
    public function __construct(
        protected AuthorizationService $authorization,
        protected TenantContext $tenant,
    ) {}

    public function viewAny(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $organization = $this->tenant->get() ?? $user->organizations()->first();

        if ($organization && $user->isOwnerOf($organization)) {
            return true;
        }

        return $this->authorization->can($user, 'rbac.view', $organization);
    }

    public function manage(User $user): bool
    {
        return $this->authorization->can($user, 'rbac.manage');
    }

    public function manageRoles(User $user): bool
    {
        return $this->authorization->can($user, 'rbac.roles.manage');
    }

    public function managePermissions(User $user): bool
    {
        return $this->authorization->can($user, 'rbac.permissions.manage');
    }

    public function manageTemplates(User $user): bool
    {
        return $this->authorization->can($user, 'rbac.templates.manage');
    }

    public function viewGroup(User $user, PermissionGroup $group): bool
    {
        return $this->authorization->can($user, 'rbac.view');
    }

    public function createGroup(User $user): bool
    {
        return $this->authorization->can($user, 'rbac.manage');
    }

    public function updateGroup(User $user, PermissionGroup $group): bool
    {
        return $this->authorization->can($user, 'rbac.manage');
    }

    public function viewRole(User $user, Role $role): bool
    {
        return $this->authorization->can($user, 'rbac.roles.view')
            && $role->organization_id === $this->tenant->id();
    }

    public function createRole(User $user): bool
    {
        return $this->authorization->can($user, 'rbac.roles.manage');
    }

    public function updateRole(User $user, Role $role): bool
    {
        return $this->authorization->can($user, 'rbac.roles.manage')
            && $role->organization_id === $this->tenant->id();
    }

    public function deleteRole(User $user, Role $role): bool
    {
        return $this->authorization->can($user, 'rbac.roles.manage')
            && $role->organization_id === $this->tenant->id()
            && ! $role->is_system;
    }

    public function updatePermission(User $user, Permission $permission): bool
    {
        return $this->authorization->can($user, 'rbac.permissions.manage');
    }

    public function installTemplate(User $user): bool
    {
        return $this->authorization->can($user, 'rbac.templates.manage');
    }

    public function manageUserRoles(User $user): bool
    {
        return $this->authorization->can($user, 'rbac.roles.manage')
            || $this->authorization->can($user, 'users.manage');
    }
}
