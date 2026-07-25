<?php

namespace Database\Seeders;

use App\Services\Dashboard\DashboardWidgetService;
use Illuminate\Database\Seeder;

class ProjectPortfolioWidgetSeeder extends Seeder
{
    public function run(): void
    {
        app(DashboardWidgetService::class)->seedSystemWidgets();
    }
}
