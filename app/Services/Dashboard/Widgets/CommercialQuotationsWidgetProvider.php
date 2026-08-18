<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\CommercialMetricsService;
use App\Services\TenantContext;

class CommercialQuotationsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'commercial_quotations';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'quotations.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        return app(CommercialMetricsService::class)->quotationMetrics($organization);
    }
}
