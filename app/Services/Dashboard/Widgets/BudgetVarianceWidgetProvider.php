<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectBudget;
use App\Models\User;
use App\Services\TenantContext;

class BudgetVarianceWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'budget_variance';
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

        $threshold = (float) config('projects.budget_variance_threshold_percent', 10);

        $budgets = ProjectBudget::query()
            ->with(['project:id,name,slug,project_number'])
            ->where('variance_total', '!=', 0)
            ->orderByRaw('ABS(variance_total) DESC')
            ->limit(8)
            ->get([
                'id', 'project_id', 'name', 'currency', 'planned_total',
                'actual_total', 'forecast_total', 'variance_total', 'status',
            ]);

        $items = $budgets->map(function (ProjectBudget $budget) {
            $planned = (float) $budget->planned_total;
            $variance = (float) $budget->variance_total;

            return [
                'id' => $budget->id,
                'name' => $budget->name,
                'status' => $budget->status,
                'currency' => $budget->currency,
                'planned_total' => $planned,
                'actual_total' => (float) $budget->actual_total,
                'forecast_total' => (float) $budget->forecast_total,
                'variance_total' => $variance,
                'variance_percent' => $planned > 0 ? round(($variance / $planned) * 100, 2) : null,
                'project' => $budget->project,
            ];
        })->values()->all();

        return [
            'threshold_percent' => $threshold,
            'count' => count($items),
            'exceeding_threshold' => collect($items)
                ->filter(fn (array $item) => abs((float) ($item['variance_percent'] ?? 0)) >= $threshold)
                ->count(),
            'budgets' => $items,
        ];
    }
}
