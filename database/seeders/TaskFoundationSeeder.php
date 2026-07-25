<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TaskFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TaskStatusSeeder::class,
            TaskPrioritySeeder::class,
            TaskPermissionSeeder::class,
            TaskWidgetSeeder::class,
            TaskQuickActionSeeder::class,
        ]);
    }
}
