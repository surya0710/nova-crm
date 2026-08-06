<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\WorkloadService;

class ResourceAvailabilityWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'resource_availability';
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
        $rows = collect(app(WorkloadService::class)->calculateTeam($organization, $from, $to))
            ->filter(fn (array $row) => ($row['status'] ?? null) !== 'overallocated')
            ->sortBy('utilization')
            ->take(8)
            ->values();

        $employees = Employee::query()
            ->whereIn('id', $rows->pluck('employee_id'))
            ->get()
            ->keyBy('id');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'available_count' => $rows->count(),
            'employees' => $rows->map(fn (array $row) => [
                'employee_id' => $row['employee_id'],
                'name' => $employees->get($row['employee_id'])?->full_name,
                'available' => $row['available'] ?? 0,
                'allocated' => $row['allocated'] ?? 0,
                'utilization' => $row['utilization'] ?? 0,
                'status' => $row['status'] ?? null,
            ])->all(),
        ];
    }
}
