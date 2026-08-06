<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProjectPortfolioPermissionSeeder::class,
            ProjectPortfolioWidgetSeeder::class,
            ProjectPortfolioQuickActionSeeder::class,
            BudgetCategorySeeder::class,
        ]);
    }
}
