<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\Rbac\PermissionService;
use Illuminate\Database\Seeder;

class DefaultOrganizationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(PermissionService::class);

        Organization::query()->each(function (Organization $organization) use ($service) {
            $service->cloneForOrganization($organization);
        });
    }
}
