<?php

namespace App\Services\Rbac;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationRoleService;

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

        $this->dashboardProvisioningService->provision($organization);

        app(\App\Services\ProjectDefaultsService::class)->seedAll($organization);

        app(\App\Services\TaskDefaultsService::class)->seedAll($organization);

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
