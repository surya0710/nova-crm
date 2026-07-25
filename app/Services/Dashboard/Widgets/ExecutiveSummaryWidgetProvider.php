<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\ExecutiveDashboardService;
use App\Services\TenantContext;

class ExecutiveSummaryWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'executive_summary';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.executive.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $payload = app(ExecutiveDashboardService::class)->forOrganization($organization, $user);

        return [
            'kpis' => $payload['kpis'] ?? [],
            'portfolio_health' => $payload['portfolio_health'] ?? [],
            'progress' => $payload['progress'] ?? [],
            'budget_status' => $payload['budget_status'] ?? [],
            'risk_overview' => $payload['risk_overview'] ?? [],
            'at_risk_count' => count($payload['at_risk_projects'] ?? []),
            'delayed_count' => count($payload['delayed_projects'] ?? []),
            'portfolio_count' => count($payload['portfolios'] ?? []),
        ];
    }
}
