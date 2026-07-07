<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrganizationRoleService
{
    public function seedPermissions(): void
    {
        $definitions = config('rbac.permissions', []);

        foreach ($definitions as $slug => [$name, $description]) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'module' => explode('.', $slug)[0],
                    'description' => $description,
                ]
            );
        }
    }

    public function seedDefaultRoles(Organization $organization): Collection
    {
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

            $permissionIds = $this->resolvePermissionIds($definition['permissions']);
            $role->permissions()->sync($permissionIds);

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

        $isOwner = $role->slug === 'organization-owner';

        DB::table('organization_user')
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->update([
                'role_id' => $role->id,
                'role' => $role->slug,
                'is_owner' => $isOwner,
            ]);
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

        return Permission::query()
            ->whereIn('slug', $permissions)
            ->pluck('id')
            ->all();
    }
}
