<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\OrganizationRoleService;
use App\Services\Rbac\PermissionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();

        $progressPermissions = Permission::query()
            ->whereNull('organization_id')
            ->where(function ($query) {
                $query->where('module', 'Projects')
                    ->where(function ($inner) {
                        $inner->where('slug', 'like', 'projects.progress.%')
                            ->orWhere('slug', 'like', 'projects.health.%')
                            ->orWhere('slug', 'like', 'projects.reports.%')
                            ->orWhere('slug', 'like', 'projects.timeline.%')
                            ->orWhere('slug', 'like', 'projects.gantt.%')
                            ->orWhere('slug', 'like', 'projects.statistics.%');
                    });
            })
            ->get()
            ->keyBy('slug');

        if ($progressPermissions->isEmpty()) {
            return;
        }

        $permissionService = app(PermissionService::class);

        Organization::query()->each(function (Organization $organization) use ($permissionService, $progressPermissions) {
            $permissionService->cloneForOrganization($organization);

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
                    ? $progressPermissions->pluck('id')->all()
                    : $progressPermissions->only(
                        is_array($configured) ? $configured : []
                    )->pluck('id')->all();

                if ($ids === []) {
                    continue;
                }

                $sync = [];
                foreach ($ids as $permissionId) {
                    $sync[$permissionId] = ['organization_id' => $organization->id];
                }

                $role->permissions()->syncWithoutDetaching($sync);
            }
        });
    }

    public function down(): void
    {
        // Permissions remain; role detach is intentional no-op for safety.
    }
};
