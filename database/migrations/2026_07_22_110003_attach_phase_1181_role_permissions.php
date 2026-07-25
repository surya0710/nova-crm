<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $newPermissions = Permission::query()
            ->whereIn('slug', [
                'organization.branches.view',
                'organization.branches.manage',
                'organization.shifts.view',
                'organization.shifts.manage',
                'organization.hr_config.manage',
                'recruitment.meeting.manage',
            ])
            ->get()
            ->keyBy('slug');

        if ($newPermissions->isEmpty()) {
            return;
        }

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
                    ? $newPermissions->pluck('id')->all()
                    : $newPermissions->only(
                        is_array($configured) ? $configured : []
                    )->pluck('id')->all();

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
        // Role grants are intentionally preserved.
    }
};
