<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectProgressTrackingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProjectProgressPermissionSeeder::class,
            ProjectProgressWidgetSeeder::class,
            ProjectProgressQuickActionSeeder::class,
        ]);
    }
}
