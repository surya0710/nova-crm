<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\SalesForecastService;
use App\Services\TenantContext;

class SalesForecastWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'sales_forecast';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'opportunities.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        return app(SalesForecastService::class)->summary($organization);
    }
}
