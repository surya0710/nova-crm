<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\OrganizationRoleService;
use App\Services\Rbac\PermissionService;
use Illuminate\Database\Seeder;

class ProjectPortfolioPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();

        $portfolioPermissions = Permission::query()
            ->whereNull('organization_id')
            ->where(function ($query) {
                $query->where('slug', 'like', 'projects.portfolios.%')
                    ->orWhere('slug', 'like', 'projects.programs.%')
                    ->orWhere('slug', 'like', 'projects.dependencies.%')
                    ->orWhere('slug', 'like', 'projects.risks.%')
                    ->orWhere('slug', 'like', 'projects.issues.%')
                    ->orWhere('slug', 'like', 'projects.baselines.%')
                    ->orWhere('slug', 'like', 'projects.budgets.%')
                    ->orWhere('slug', 'like', 'projects.forecasts.%')
                    ->orWhere('slug', 'like', 'projects.executive.%')
                    ->orWhere('slug', 'like', 'projects.portfolio_reports.%');
            })
            ->get()
            ->keyBy('slug');

        if ($portfolioPermissions->isEmpty()) {
            return;
        }

        $permissionService = app(PermissionService::class);

        Organization::query()->each(function (Organization $organization) use ($permissionService, $portfolioPermissions) {
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
                    ? $portfolioPermissions->pluck('id')->all()
                    : $portfolioPermissions->only(
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
}
