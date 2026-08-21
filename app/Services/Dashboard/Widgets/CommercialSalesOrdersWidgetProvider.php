<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\CommercialMetricsService;
use App\Services\TenantContext;

class CommercialSalesOrdersWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'commercial_sales_orders';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'sales_orders.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        return app(CommercialMetricsService::class)->salesOrderMetrics($organization);
    }
}
