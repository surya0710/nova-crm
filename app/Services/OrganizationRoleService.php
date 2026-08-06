<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrganizationRoleService
{
    public function seedPermissions(): void
    {
        $definitions = array_merge(
            config('rbac.permissions', []),
            config('dynamic_rbac.rbac_permissions', [])
        );

        $moduleGroupMap = config('dynamic_rbac.module_group_map', []);
        $groups = Schema::hasTable('permission_groups')
            ? \App\Models\PermissionGroup::query()->whereNull('organization_id')->pluck('id', 'slug')
            : collect();

        foreach ($definitions as $slug => [$name, $description]) {
            $module = explode('.', $slug)[0];
            $groupSlug = $moduleGroupMap[$module] ?? 'administration';

            $lookup = ['slug' => $slug];
            $values = [
                'name' => $name,
                'module' => $module,
                'description' => $description,
            ];

            if (Schema::hasColumn('permissions', 'organization_id')) {
                $lookup['organization_id'] = null;
            }

            if (Schema::hasColumn('permissions', 'permission_group_id')) {
                $values['permission_group_id'] = $groups[$groupSlug] ?? null;
            }

            if (Schema::hasColumn('permissions', 'is_system')) {
                $values['is_system'] = true;
                $values['is_active'] = true;
            }

            Permission::query()->updateOrCreate($lookup, $values);
        }
    }

    public function seedDefaultRoles(Organization $organization): Collection
    {
        if (Schema::hasTable('permission_templates') && class_exists(\App\Services\Rbac\OrganizationProvisioningService::class)) {
            app(\App\Services\Rbac\OrganizationProvisioningService::class)->provision($organization);

            return $organization->roles()->get();
        }

        $roles = collect();

        foreach (config('rbac.roles', []) as $slug => $definition) {
            $role = Role::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'] ?? null,
                    'is_system' => true,
                ]
            );

            $this->syncRolePermissions($role, $definition['permissions']);

            $roles->push($role);
        }

        return $roles;
    }

    public function assignRole(User $user, Organization $organization, Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::query()
                ->where('organization_id', $organization->id)
                ->where('slug', $role)
                ->firstOrFail();
        }

        $isOwner = in_array($role->slug, ['organization-owner', 'organization-administrator'], true);

        DB::table('organization_user')
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->update([
                'role_id' => $role->id,
                'role' => $role->slug,
                'is_owner' => $isOwner,
            ]);

        if (class_exists(\App\Services\Rbac\AuthorizationService::class)) {
            app(\App\Services\Rbac\AuthorizationService::class)->forgetUserCache($user, $organization);
        }
    }

    public function syncRolePermissions(Role $role, array|string $permissions): void
    {
        $permissionIds = $this->resolvePermissionIds($permissions);

        if (Schema::hasTable('role_permissions')) {
            RolePermission::query()
                ->where('organization_id', $role->organization_id)
                ->where('role_id', $role->id)
                ->whereNotIn('permission_id', $permissionIds)
                ->delete();

            foreach ($permissionIds as $permissionId) {
                RolePermission::query()->firstOrCreate([
                    'organization_id' => $role->organization_id,
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                ]);
            }

            return;
        }

        DB::table('role_permission')->where('role_id', $role->id)->delete();

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permission')->insertOrIgnore([
                'role_id' => $role->id,
                'permission_id' => $permissionId,
            ]);
        }
    }

    /**
     * @param  array<int, string>|string  $permissions
     * @return array<int, int>
     */
    protected function resolvePermissionIds(array|string $permissions): array
    {
        if ($permissions === '*') {
            return Permission::query()->pluck('id')->all();
        }

        if (is_string($permissions)) {
            $rolePermissions = config("rbac.roles.{$permissions}.permissions");
            if ($rolePermissions) {
                $permissions = $rolePermissions;
            } else {
                $permissions = config("dynamic_rbac.system_roles.{$permissions}.permissions", []);
            }
        }

        if ($permissions === '*') {
            return Permission::query()->pluck('id')->all();
        }

        return Permission::query()
            ->whereIn('slug', $permissions)
            ->pluck('id')
            ->all();
    }
}
