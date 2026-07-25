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

        $collaborationPermissions = Permission::query()
            ->whereNull('organization_id')
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('module', 'Projects')
                        ->where(function ($slugQuery) {
                            $slugQuery->where('slug', 'like', 'projects.labels.%')
                                ->orWhere('slug', 'like', 'projects.watchers.%')
                                ->orWhere('slug', 'like', 'projects.mentions.%')
                                ->orWhere('slug', 'like', 'projects.templates.%')
                                ->orWhere('slug', 'like', 'projects.recurrence.%')
                                ->orWhere('slug', 'like', 'projects.collaboration.%')
                                ->orWhere('slug', 'like', 'projects.automation.%')
                                ->orWhere('slug', 'like', 'projects.calendar.%')
                                ->orWhere('slug', 'like', 'projects.notifications.%');
                        });
                })->orWhere(function ($inner) {
                    $inner->where('module', 'Tasks')
                        ->where('slug', 'tasks.labels.manage');
                });
            })
            ->get()
            ->keyBy('slug');

        if ($collaborationPermissions->isEmpty()) {
            return;
        }

        $permissionService = app(PermissionService::class);

        Organization::query()->each(function (Organization $organization) use ($permissionService, $collaborationPermissions) {
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
                    ? $collaborationPermissions->pluck('id')->all()
                    : $collaborationPermissions->only(
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
