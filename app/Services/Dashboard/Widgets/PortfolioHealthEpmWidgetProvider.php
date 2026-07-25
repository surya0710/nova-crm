<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\PortfolioStatisticsService;
use App\Services\TenantContext;

class PortfolioHealthEpmWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'portfolio_health_epm';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.portfolios.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $statistics = app(PortfolioStatisticsService::class);

        $portfolios = Portfolio::query()
            ->whereNull('archived_at')
            ->with('projects')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $items = $portfolios->map(function (Portfolio $portfolio) use ($statistics) {
            $stats = $statistics->forPortfolio($portfolio);

            return [
                'id' => $portfolio->id,
                'name' => $portfolio->name,
                'code' => $portfolio->code,
                'project_count' => $stats['project_count'] ?? 0,
                'average_completion_percentage' => $stats['average_completion_percentage'] ?? 0,
                'health' => $stats['health'] ?? [],
                'risk_score' => $stats['risk_score'] ?? 0,
            ];
        })->values()->all();

        $totals = [
            'on_track' => 0,
            'at_risk' => 0,
            'delayed' => 0,
            'completed' => 0,
            'archived' => 0,
        ];

        foreach ($items as $item) {
            foreach ($totals as $status => $_) {
                $totals[$status] += (int) ($item['health'][$status] ?? 0);
            }
        }

        return [
            'count' => count($items),
            'health' => $totals,
            'portfolios' => $items,
        ];
    }
}
