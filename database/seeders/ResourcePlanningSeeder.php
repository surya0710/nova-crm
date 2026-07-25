<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ResourcePlanningSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ResourcePermissionSeeder::class,
            ResourceWidgetSeeder::class,
            ResourceQuickActionSeeder::class,
        ]);
    }
}
