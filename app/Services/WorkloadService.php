<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\ResourceAllocation;
use App\Models\WorkloadSnapshot;
use App\Services\Hrms\LeaveService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkloadService
{
    public function __construct(
        protected ResourceCalendarService $calendars,
        protected LeaveService $leaveService,
    ) {}

    /**
     * @return array{
     *     employee_id: int,
     *     from: string,
     *     to: string,
     *     capacity: float,
     *     allocated: float,
     *     available: float,
     *     utilization: float,
     *     status: string,
     *     days: list<array<string, mixed>>
     * }
     */
    public function calculateForEmployee(Employee $employee, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $allocations = ResourceAllocation::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->whereDate('planned_start_date', '<=', $to->toDateString())
            ->whereDate('planned_end_date', '>=', $from->toDateString())
            ->get();

        $leaveByDate = $this->leaveFactorByDate($employee, $from, $to);

        $days = [];
        $capacity = 0.0;
        $allocated = 0.0;
        $available = 0.0;

        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $dateKey = $cursor->toDateString();
            $dayHours = $this->calendars->workingHoursForDay($employee, $cursor);
            $leaveFactor = $leaveByDate[$dateKey] ?? 1.0;
            $availableHours = round($dayHours * $leaveFactor, 2);

            $percentage = (int) $allocations
                ->filter(function (ResourceAllocation $allocation) use ($cursor) {
                    return $allocation->planned_start_date->lte($cursor)
                        && $allocation->planned_end_date->gte($cursor);
                })
                ->sum('allocation_percentage');

            $allocatedHours = round($dayHours * ($percentage / 100), 2);

            $capacity += $dayHours;
            $available += $availableHours;
            $allocated += $allocatedHours;

            $days[] = [
                'date' => $dateKey,
                'capacity_hours' => $dayHours,
                'available_hours' => $availableHours,
                'allocated_hours' => $allocatedHours,
                'allocation_percentage' => $percentage,
                'leave_factor' => $leaveFactor,
                'utilization' => $availableHours > 0
                    ? round(($allocatedHours / $availableHours) * 100, 2)
                    : ($allocatedHours > 0 ? 100.0 : 0.0),
            ];

            $cursor->addDay();
        }

        $utilization = $available > 0
            ? round(($allocated / $available) * 100, 2)
            : ($allocated > 0 ? 100.0 : 0.0);

        return [
            'employee_id' => (int) $employee->id,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'capacity' => round($capacity, 2),
            'allocated' => round($allocated, 2),
            'available' => round($available, 2),
            'utilization' => $utilization,
            'status' => $this->statusForUtilization($utilization),
            'days' => $days,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function calculateTeam(Organization $organization, Carbon $from, Carbon $to): array
    {
        $employees = $this->activeEmployees($organization);
        $results = [];

        foreach ($employees as $employee) {
            $results[] = $this->calculateForEmployee($employee, $from, $to);
        }

        return $results;
    }

    public function snapshotEmployee(Employee $employee, Carbon $date): WorkloadSnapshot
    {
        $result = $this->calculateForEmployee($employee, $date->copy()->startOfDay(), $date->copy()->startOfDay());

        return WorkloadSnapshot::query()->updateOrCreate(
            [
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'snapshot_date' => $date->toDateString(),
            ],
            [
                'allocated_hours' => $result['allocated'],
                'available_hours' => $result['available'],
                'utilization_percentage' => $result['utilization'],
                'overall_status' => $result['status'],
            ]
        );
    }

    /**
     * @return Collection<int, WorkloadSnapshot>
     */
    public function snapshotTeam(Organization $organization, Carbon $date): Collection
    {
        return DB::transaction(function () use ($organization, $date) {
            $snapshots = collect();

            foreach ($this->activeEmployees($organization) as $employee) {
                $snapshots->push($this->snapshotEmployee($employee, $date));
            }

            return $snapshots;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function detectOverallocations(Organization $organization, Carbon $from, Carbon $to): array
    {
        $over = [];

        foreach ($this->calculateTeam($organization, $from, $to) as $row) {
            if (($row['status'] ?? null) === 'overallocated') {
                $over[] = $row;
            }
        }

        return $over;
    }

    public function statusForUtilization(float $utilization): string
    {
        $under = (float) config('resources.underutilization_threshold', 50);
        $over = (float) config('resources.overallocation_threshold', 100);

        if ($utilization < $under) {
            return 'underutilized';
        }

        if ($utilization > $over) {
            return 'overallocated';
        }

        return 'optimal';
    }

    /**
     * @return Collection<int, Employee>
     */
    protected function activeEmployees(Organization $organization): Collection
    {
        $statuses = config('hrms.leave_applicable_employee_statuses', ['active', 'probation', 'notice_period']);

        return Employee::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereIn('status', $statuses)
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return array<string, float> date => leave factor (0 full leave, 0.5 half day, 1 none)
     */
    protected function leaveFactorByDate(Employee $employee, Carbon $from, Carbon $to): array
    {
        $factors = [];
        $leaves = $this->leaveService->getApprovedLeaveForDateRange($employee, $from, $to);

        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $dateKey = $cursor->toDateString();
            $factor = 1.0;

            foreach ($leaves as $leave) {
                if ($leave->start_date->gt($cursor) || $leave->end_date->lt($cursor)) {
                    continue;
                }

                if ($leave->is_half_day) {
                    $factor = min($factor, 0.5);
                } else {
                    $factor = 0.0;
                    break;
                }
            }

            $factors[$dateKey] = $factor;
            $cursor->addDay();
        }

        return $factors;
    }
}
