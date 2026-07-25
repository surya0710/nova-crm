<?php

namespace Database\Seeders;

use App\Services\Dashboard\DashboardProvisioningService;
use App\Services\Dashboard\DashboardWidgetService;
use App\Services\Dashboard\QuickActionService;
use Illuminate\Database\Seeder;

class DashboardPlatformSeeder extends Seeder
{
    public function run(): void
    {
        app(DashboardWidgetService::class)->seedSystemWidgets();
        app(QuickActionService::class)->seedSystemActions();
        app(DashboardProvisioningService::class)->provisionForAllOrganizations();
    }
}
