<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\WorkloadService;

class ResourceUtilizationSummaryWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'resource_utilization_summary';
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

        $statusCounts = [
            'underutilized' => 0,
            'optimal' => 0,
            'overallocated' => 0,
        ];

        $totalUtilization = 0.0;

        foreach ($rows as $row) {
            $status = $row['status'] ?? 'optimal';
            if (array_key_exists($status, $statusCounts)) {
                $statusCounts[$status]++;
            }

            $totalUtilization += (float) ($row['utilization_percentage'] ?? 0);
        }

        $employeeCount = count($rows);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'employee_count' => $employeeCount,
            'average_utilization' => $employeeCount > 0
                ? round($totalUtilization / $employeeCount, 1)
                : 0.0,
            'status_counts' => $statusCounts,
        ];
    }
}
