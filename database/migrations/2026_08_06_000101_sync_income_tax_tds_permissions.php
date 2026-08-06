<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Services\OrganizationRoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();

        $permissions = Permission::query()
            ->whereIn('slug', [
                'tax.view',
                'tax.manage',
                'tax.verify',
                'tax.calculate',
                'form16.generate',
            ])
            ->get()
            ->keyBy('slug');

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
                    ? $permissions->pluck('id')->all()
                    : $permissions->only($configured)->pluck('id')->all();

                if ($ids === []) {
                    continue;
                }

                if (Schema::hasTable('role_permissions') && Schema::hasColumn('role_permissions', 'organization_id')) {
                    foreach ($ids as $permissionId) {
                        RolePermission::query()->firstOrCreate([
                            'organization_id' => $organization->id,
                            'role_id' => $role->id,
                            'permission_id' => $permissionId,
                        ]);
                    }

                    continue;
                }

                $role->permissions()->syncWithoutDetaching($ids);
            }
        }
    }

    public function down(): void
    {
        // Permission records and role grants are intentionally preserved.
    }
};
