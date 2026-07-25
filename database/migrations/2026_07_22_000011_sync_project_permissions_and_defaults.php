<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Services\OrganizationRoleService;
use App\Services\ProjectDefaultsService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();

        $projectPermissionIds = Permission::query()
            ->where('module', 'projects')
            ->pluck('id', 'slug');

        $defaults = app(ProjectDefaultsService::class);

        foreach (Organization::query()->cursor() as $organization) {
            $defaults->seedAll($organization);

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
                    ? $projectPermissionIds->values()->all()
                    : $projectPermissionIds->only($configured)->values()->all();

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
        // Permission records, role grants, and project defaults are intentionally preserved.
    }
};
