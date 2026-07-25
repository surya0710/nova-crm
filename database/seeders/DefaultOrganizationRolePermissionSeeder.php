<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Services\OrganizationRoleService;
use Illuminate\Database\Seeder;

class DefaultOrganizationRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(OrganizationRoleService::class);

        Organization::query()->each(function (Organization $organization) use ($service) {
            foreach (config('rbac.roles', []) as $slug => $definition) {
                $role = Role::query()
                    ->where('organization_id', $organization->id)
                    ->where('slug', $slug)
                    ->first();

                if ($role) {
                    $service->syncRolePermissions($role, $definition['permissions']);
                }
            }
        });
    }
}
