<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectBudget;
use App\Models\User;
use App\Services\TenantContext;

class BudgetHealthWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'budget_health';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.budgets.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $budgets = ProjectBudget::query()
            ->whereIn('status', ['approved', 'active', 'draft'])
            ->get(['id', 'planned_total', 'actual_total', 'forecast_total', 'variance_total', 'status']);

        $planned = round((float) $budgets->sum('planned_total'), 2);
        $actual = round((float) $budgets->sum('actual_total'), 2);
        $forecast = round((float) $budgets->sum('forecast_total'), 2);
        $variance = round((float) $budgets->sum('variance_total'), 2);

        $byStatus = ProjectBudget::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return [
            'count' => $budgets->count(),
            'planned_total' => $planned,
            'actual_total' => $actual,
            'forecast_total' => $forecast,
            'variance_total' => $variance,
            'variance_percent' => $planned > 0 ? round(($variance / $planned) * 100, 2) : null,
            'by_status' => collect(config('projects.budget_statuses', []))->map(fn (string $label, string $status) => [
                'status' => $status,
                'label' => $label,
                'count' => (int) ($byStatus[$status] ?? 0),
            ])->values()->all(),
        ];
    }
}
