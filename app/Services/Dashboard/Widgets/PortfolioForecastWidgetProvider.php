<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\ForecastService;
use App\Services\TenantContext;

class PortfolioForecastWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'portfolio_forecast';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.forecasts.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $forecastService = app(ForecastService::class);

        $portfolios = Portfolio::query()
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->with('projects')
            ->orderBy('name')
            ->limit(5)
            ->get();

        if ($portfolios->isEmpty()) {
            $portfolios = Portfolio::query()
                ->whereNull('archived_at')
                ->orderBy('name')
                ->limit(5)
                ->get();
        }

        $forecasts = $portfolios->map(function (Portfolio $portfolio) use ($forecastService) {
            $forecast = $forecastService->forPortfolio($portfolio, null, false);

            return [
                'portfolio_id' => $portfolio->id,
                'name' => $portfolio->name,
                'code' => $portfolio->code,
                'delayed_project_count' => $forecast['delayed_project_count'] ?? 0,
                'overrun_project_count' => $forecast['overrun_project_count'] ?? 0,
                'average_risk_score' => $forecast['average_risk_score'] ?? 0,
                'portfolio_capacity' => $forecast['portfolio_capacity'] ?? [],
            ];
        })->values()->all();

        return [
            'count' => count($forecasts),
            'delayed_project_count' => collect($forecasts)->sum('delayed_project_count'),
            'overrun_project_count' => collect($forecasts)->sum('overrun_project_count'),
            'average_risk_score' => round((float) collect($forecasts)->avg('average_risk_score'), 2),
            'forecasts' => $forecasts,
        ];
    }
}
