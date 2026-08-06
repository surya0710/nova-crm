<?php

namespace Database\Seeders;

use App\Services\OrganizationRoleService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();
    }
}
