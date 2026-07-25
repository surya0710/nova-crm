<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectCollaborationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProjectCollaborationPermissionSeeder::class,
            ProjectCollaborationWidgetSeeder::class,
            ProjectCollaborationQuickActionSeeder::class,
            ProjectLabelSeeder::class,
        ]);
    }
}
