<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\CommercialMetricsService;
use App\Services\TenantContext;

class CommercialInvoicesWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'commercial_invoices';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'invoices.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        return app(CommercialMetricsService::class)->invoiceMetrics($organization);
    }
}
