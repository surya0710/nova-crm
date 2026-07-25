<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Services\OrganizationRoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();

        $dashboardPermissionIds = Permission::query()
            ->where('module', 'dashboard')
            ->pluck('id', 'slug');

        foreach (Organization::query()->cursor() as $organization) {
            foreach (config('rbac.roles', []) as $slug => $definition) {
                $role = Role::query()
                    ->where('organization_id', $organization->id)
                    ->where('slug', $slug)
                    ->first();

                if (! $role) {
                    continue;
                }

                $configured = $definition['permissions'];
                $ids = $configured === '*'
                    ? $dashboardPermissionIds->values()->all()
                    : $dashboardPermissionIds->only($configured)->values()->all();

                foreach ($ids as $permissionId) {
                    RolePermission::query()->firstOrCreate([
                        'organization_id' => $organization->id,
                        'role_id' => $role->id,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Permission records and role grants are intentionally preserved.
    }
};
