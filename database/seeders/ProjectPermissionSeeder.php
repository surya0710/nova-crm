<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\OrganizationRoleService;
use App\Services\Rbac\PermissionService;
use Illuminate\Database\Seeder;

class ProjectPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();

        $projectPermissions = Permission::query()
            ->whereNull('organization_id')
            ->where(function ($query) {
                $query->where('module', 'projects')
                    ->orWhere('slug', 'like', 'projects.%');
            })
            ->get()
            ->keyBy('slug');

        if ($projectPermissions->isEmpty()) {
            return;
        }

        $permissionService = app(PermissionService::class);

        Organization::query()->each(function (Organization $organization) use ($permissionService, $projectPermissions) {
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
                    ? $projectPermissions->pluck('id')->all()
                    : $projectPermissions->only($configured)->pluck('id')->all();

                if ($ids !== []) {
                    $role->permissions()->syncWithoutDetaching($ids);
                }
            }
        });
    }
}
