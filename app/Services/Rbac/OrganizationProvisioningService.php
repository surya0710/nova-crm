<?php

namespace App\Services\Rbac;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationRoleService;
use Illuminate\Support\Facades\Schema;

class OrganizationProvisioningService
{
    public function __construct(
        protected OrganizationRoleService $organizationRoleService,
        protected PermissionService $permissionService,
        protected PermissionTemplateService $permissionTemplateService,
        protected UserRoleService $userRoleService,
        protected \App\Services\Dashboard\DashboardProvisioningService $dashboardProvisioningService,
    ) {}

    public function provision(Organization $organization, ?User $owner = null): void
    {
        $this->organizationRoleService->seedPermissions();

        $this->permissionService->cloneForOrganization($organization);

        $this->permissionTemplateService->applyDefault($organization);

        $this->ensureLegacyRoles($organization);

        // Skip modules whose tables are not migrated yet (e.g. RBAC sync before dashboard/projects).
        if (Schema::hasTable('dashboard_widgets')) {
            $this->dashboardProvisioningService->provision($organization);
        }

        if (Schema::hasTable('project_categories')) {
            app(\App\Services\ProjectDefaultsService::class)->seedAll($organization);
        }

        if (Schema::hasTable('task_statuses')) {
            app(\App\Services\TaskDefaultsService::class)->seedAll($organization);
        }

        if ($owner) {
            $adminRole = Role::query()
                ->where('organization_id', $organization->id)
                ->whereIn('slug', ['organization-administrator', 'organization-owner'])
                ->first();

            if ($adminRole) {
                $this->userRoleService->assign($owner, $organization, $adminRole, null, true);
            }
        }
    }

    protected function ensureLegacyRoles(Organization $organization): void
    {
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
                    'is_active' => true,
                ]
            );

            $this->organizationRoleService->syncRolePermissions($role, $definition['permissions']);
        }
    }
}
