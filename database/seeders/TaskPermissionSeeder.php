<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\OrganizationRoleService;
use App\Services\Rbac\PermissionService;
use Illuminate\Database\Seeder;

class TaskPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();

        $taskPermissions = Permission::query()
            ->whereNull('organization_id')
            ->where(function ($query) {
                $query->where('module', 'tasks')
                    ->orWhere('slug', 'like', 'tasks.%');
            })
            ->get()
            ->keyBy('slug');

        if ($taskPermissions->isEmpty()) {
            return;
        }

        $permissionService = app(PermissionService::class);

        Organization::query()->each(function (Organization $organization) use ($permissionService, $taskPermissions) {
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
                    ? $taskPermissions->pluck('id')->all()
                    : $taskPermissions->only($configured)->pluck('id')->all();

                if ($ids !== []) {
                    $role->permissions()->syncWithoutDetaching($ids);
                }
            }
        });
    }
}
