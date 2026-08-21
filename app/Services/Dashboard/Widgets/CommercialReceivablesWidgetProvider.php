<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\CommercialMetricsService;
use App\Services\TenantContext;

class CommercialReceivablesWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'commercial_receivables';
    }

    public function subscriptionModule(): ?string
    {
        return 'crm';
    }

    public function permissionSlug(): ?string
    {
        return 'invoices.view';
    }

    public function authorize(User $user, Organization $organization): bool
    {
        if (! $this->subscriptionService->moduleAllowed($organization, $this->subscriptionModule())) {
            return false;
        }

        return $user->hasPermission('invoices.view', $organization)
            || $user->hasPermission('finance.view', $organization);
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        return app(CommercialMetricsService::class)->receivableMetrics($organization);
    }
}
