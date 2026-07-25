<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\WorkloadService;

class TeamWorkloadWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'team_workload';
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
        $rows = app(WorkloadService::class)->calculateTeam($organization, $from, $to);
        $employees = Employee::query()
            ->whereIn('id', collect($rows)->pluck('employee_id'))
            ->get()
            ->keyBy('id');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'employee_count' => count($rows),
            'overallocated' => count(array_filter($rows, fn ($r) => ($r['status'] ?? null) === 'overallocated')),
            'underutilized' => count(array_filter($rows, fn ($r) => ($r['status'] ?? null) === 'underutilized')),
            'rows' => collect($rows)->take(8)->map(function (array $row) use ($employees) {
                return [
                    ...$row,
                    'employee_name' => $employees->get($row['employee_id'])?->full_name,
                ];
            })->values()->all(),
        ];
    }
}
