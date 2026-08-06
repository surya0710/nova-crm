<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DynamicRbacSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionGroupSeeder::class,
            PermissionSeeder::class,
            RoleTemplateSeeder::class,
            PermissionTemplateSeeder::class,
            PermissionTemplateRoleSeeder::class,
            PermissionTemplatePermissionSeeder::class,
            DefaultOrganizationPermissionSeeder::class,
            DefaultOrganizationRoleSeeder::class,
            DefaultOrganizationRolePermissionSeeder::class,
        ]);
    }
}
