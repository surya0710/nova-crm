<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProjectCategorySeeder::class,
            ProjectTypeSeeder::class,
            ProjectStatusSeeder::class,
            ProjectLifecycleSeeder::class,
            ProjectRoleSeeder::class,
            ProjectPermissionSeeder::class,
            ProjectWidgetSeeder::class,
            ProjectQuickActionSeeder::class,
        ]);
    }
}
