<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\WorkloadService;

class OverallocatedEmployeesWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'overallocated_employees';
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

        $from = now()->startOfWeek();
        $to = now()->endOfWeek();
        $rows = collect(app(WorkloadService::class)->detectOverallocations($organization, $from, $to))
            ->sortByDesc('utilization')
            ->take(8)
            ->values();

        $employees = Employee::query()
            ->whereIn('id', $rows->pluck('employee_id'))
            ->get()
            ->keyBy('id');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'count' => $rows->count(),
            'employees' => $rows->map(fn (array $row) => [
                'employee_id' => $row['employee_id'],
                'name' => $employees->get($row['employee_id'])?->full_name,
                'utilization' => $row['utilization'] ?? 0,
                'allocated' => $row['allocated'] ?? 0,
                'available' => $row['available'] ?? 0,
            ])->all(),
        ];
    }
}
