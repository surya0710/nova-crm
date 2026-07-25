<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectRisk;
use App\Models\User;
use App\Services\RiskManagementService;
use App\Services\TenantContext;

class RiskHeatmapWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'risk_heatmap';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.risks.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $matrix = app(RiskManagementService::class)->matrix($organization);

        $openCount = ProjectRisk::query()
            ->whereNotIn('status', ['closed'])
            ->count();

        return [
            'open_count' => $openCount,
            'matrix' => $matrix['matrix'],
            'cells' => $matrix['cells'],
        ];
    }
}
