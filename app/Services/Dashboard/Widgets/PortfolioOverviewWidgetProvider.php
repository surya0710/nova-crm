<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\TenantContext;

class PortfolioOverviewWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'portfolio_overview';
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

        $query = Portfolio::query()->whereNull('archived_at');

        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $portfolios = (clone $query)
            ->withCount('projects')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'name', 'code', 'status', 'color', 'updated_at']);

        return [
            'count' => (clone $query)->count(),
            'by_status' => collect(config('projects.portfolio_statuses', []))->map(fn (string $label, string $status) => [
                'status' => $status,
                'label' => $label,
                'count' => (int) ($byStatus[$status] ?? 0),
            ])->values()->all(),
            'portfolios' => $portfolios,
        ];
    }
}
