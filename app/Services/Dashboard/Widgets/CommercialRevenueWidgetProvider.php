<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\CommercialMetricsService;
use App\Services\RevenueService;
use App\Services\TenantContext;

class CommercialRevenueWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'commercial_revenue';
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

        $metrics = app(CommercialMetricsService::class);
        $invoices = $metrics->invoiceMetrics($organization);

        return [
            'revenue' => $invoices['revenue'],
            'outstanding_value' => $invoices['outstanding_value'],
            ...$metrics->revenueBreakdown(app(RevenueService::class), $organization),
        ];
    }
}
