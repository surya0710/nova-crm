<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\CapacityPlanningService;
use App\Services\TenantContext;

class UpcomingCapacityRisksWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'upcoming_capacity_risks';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'resources.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $risks = collect(app(CapacityPlanningService::class)->upcomingRisks($organization))
            ->take(8)
            ->values();

        $employees = Employee::query()
            ->whereIn('id', $risks->pluck('employee_id'))
            ->get()
            ->keyBy('id');

        return [
            'days' => (int) config('resources.capacity_risk_days', 14),
            'count' => $risks->count(),
            'risks' => $risks->map(fn (array $risk) => [
                ...$risk,
                'employee_name' => $employees->get($risk['employee_id'])?->full_name,
            ])->all(),
        ];
    }
}
