<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // System role definitions are stored in config/dynamic_rbac.php
        // and applied per-organization via PermissionTemplateService.
    }
}
