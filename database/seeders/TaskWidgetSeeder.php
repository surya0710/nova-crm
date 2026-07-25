<?php

namespace Database\Seeders;

use App\Services\Dashboard\DashboardWidgetService;
use Illuminate\Database\Seeder;

class TaskWidgetSeeder extends Seeder
{
    public function run(): void
    {
        app(DashboardWidgetService::class)->seedSystemWidgets();
    }
}
