<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\Rbac\OrganizationProvisioningService;
use Illuminate\Database\Seeder;

class DefaultOrganizationRoleSeeder extends Seeder
{
    public function run(): void
    {
        $provisioning = app(OrganizationProvisioningService::class);

        Organization::query()->each(function (Organization $organization) use ($provisioning) {
            $provisioning->provision($organization);
        });
    }
}
