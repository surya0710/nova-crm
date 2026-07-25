<?php

namespace App\Services\Rbac;

use App\Events\Rbac\PermissionAssigned;
use App\Events\Rbac\PermissionRevoked;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RolePermissionService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function assign(Organization $organization, Role $role, Permission $permission, ?User $actor = null): void
    {
        RolePermission::query()->firstOrCreate([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        event(new PermissionAssigned($role, $permission, $actor));
        $this->auditLogger->log($role, 'rbac.permission.assigned', [
            'permission_slug' => $permission->slug,
        ], $actor);
    }

    public function revoke(Organization $organization, Role $role, Permission $permission, ?User $actor = null): void
    {
        RolePermission::query()
            ->where('organization_id', $organization->id)
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->delete();

        event(new PermissionRevoked($role, $permission, $actor));
        $this->auditLogger->log($role, 'rbac.permission.revoked', [
            'permission_slug' => $permission->slug,
        ], $actor);
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function sync(Organization $organization, Role $role, array $permissionIds, ?User $actor = null): void
    {
        DB::transaction(function () use ($organization, $role, $permissionIds, $actor) {
            $existing = RolePermission::query()
                ->where('organization_id', $organization->id)
                ->where('role_id', $role->id)
                ->pluck('permission_id')
                ->all();

            $toAdd = array_diff($permissionIds, $existing);
            $toRemove = array_diff($existing, $permissionIds);

            foreach ($toRemove as $permissionId) {
                $permission = Permission::query()->find($permissionId);
                if ($permission) {
                    $this->revoke($organization, $role, $permission, $actor);
                }
            }

            foreach ($toAdd as $permissionId) {
                $permission = Permission::query()->find($permissionId);
                if ($permission) {
                    $this->assign($organization, $role, $permission, $actor);
                }
            }

            $this->auditLogger->log($role, 'rbac.permission.synced', [
                'added' => count($toAdd),
                'removed' => count($toRemove),
                'total' => count($permissionIds),
            ], $actor);
        });
    }

    /**
     * @param  array<string, array<int, int>>  $matrix  role_id => permission_ids
     */
    public function bulkUpdate(Organization $organization, array $matrix, ?User $actor = null): void
    {
        DB::transaction(function () use ($organization, $matrix, $actor) {
            foreach ($matrix as $roleId => $permissionIds) {
                $role = Role::query()
                    ->where('organization_id', $organization->id)
                    ->findOrFail($roleId);

                $this->sync($organization, $role, $permissionIds, $actor);
            }
        });
    }

    public function matrix(Organization $organization, ?string $module = null): array
    {
        $roles = Role::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderByDesc('hierarchy_level')
            ->get();

        $permissionsQuery = Permission::query()
            ->forOrganization($organization)
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('name');

        if ($module) {
            $permissionsQuery->where('module', $module);
        }

        $permissions = $permissionsQuery->get();

        $assignments = RolePermission::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->groupBy('role_id')
            ->map(fn (Collection $items) => $items->pluck('permission_id')->all());

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'assignments' => $assignments,
        ];
    }
}
