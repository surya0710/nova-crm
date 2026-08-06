<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\ResourceAllocation;
use App\Models\Task;
use App\Services\Hrms\LeaveService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CapacityPlanningService
{
    public function __construct(
        protected ResourceCalendarService $calendars,
        protected WorkloadService $workload,
        protected LeaveService $leaveService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forecast(Organization|Employee $subject, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        if ($subject instanceof Employee) {
            return $this->forecastEmployee($subject, $from, $to);
        }

        $employees = $this->activeEmployees($subject);
        $rows = [];

        foreach ($employees as $employee) {
            $rows[] = $this->forecastEmployee($employee, $from, $to);
        }

        return [
            'organization_id' => (int) $subject->id,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'employees' => $rows,
            'summary' => [
                'employee_count' => count($rows),
                'total_available_hours' => round(array_sum(array_column($rows, 'available_hours')), 2),
                'total_allocated_hours' => round(array_sum(array_column($rows, 'allocated_hours')), 2),
                'total_open_task_hours' => round(array_sum(array_column($rows, 'open_task_estimated_hours')), 2),
                'total_forecast_load_hours' => round(array_sum(array_column($rows, 'forecast_load_hours')), 2),
                'overallocated_count' => count(array_filter(
                    $rows,
                    fn (array $row) => ($row['status'] ?? null) === 'overallocated'
                )),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function upcomingRisks(Organization $organization, ?int $days = null): array
    {
        $days ??= (int) config('resources.capacity_risk_days', 14);
        $from = now()->startOfDay();
        $to = now()->copy()->addDays(max(1, $days))->startOfDay();

        $risks = [];

        foreach ($this->activeEmployees($organization) as $employee) {
            $forecast = $this->forecastEmployee($employee, $from, $to);

            if (($forecast['status'] ?? null) !== 'overallocated') {
                continue;
            }

            $risks[] = [
                'employee_id' => $forecast['employee_id'],
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'utilization' => $forecast['utilization'],
                'available_hours' => $forecast['available_hours'],
                'allocated_hours' => $forecast['allocated_hours'],
                'open_task_estimated_hours' => $forecast['open_task_estimated_hours'],
                'forecast_load_hours' => $forecast['forecast_load_hours'],
                'status' => $forecast['status'],
            ];
        }

        usort($risks, fn (array $a, array $b) => ($b['utilization'] <=> $a['utilization']));

        return $risks;
    }

    /**
     * @return array<string, mixed>
     */
    protected function forecastEmployee(Employee $employee, Carbon $from, Carbon $to): array
    {
        $workload = $this->workload->calculateForEmployee($employee, $from, $to);
        $openTaskHours = $this->openTaskEstimatedHours($employee, $from, $to);
        $leaveDays = $this->approvedLeaveDays($employee, $from, $to);

        $forecastLoad = round((float) $workload['allocated'] + $openTaskHours, 2);
        $available = (float) $workload['available'];
        $utilization = $available > 0
            ? round(($forecastLoad / $available) * 100, 2)
            : ($forecastLoad > 0 ? 100.0 : 0.0);

        return [
            'employee_id' => (int) $employee->id,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'capacity_hours' => $workload['capacity'],
            'available_hours' => $available,
            'allocated_hours' => $workload['allocated'],
            'open_task_estimated_hours' => $openTaskHours,
            'leave_days' => $leaveDays,
            'forecast_load_hours' => $forecastLoad,
            'utilization' => $utilization,
            'status' => $this->workload->statusForUtilization($utilization),
            'allocations' => ResourceAllocation::query()
                ->where('organization_id', $employee->organization_id)
                ->where('employee_id', $employee->id)
                ->whereDate('planned_start_date', '<=', $to->toDateString())
                ->whereDate('planned_end_date', '>=', $from->toDateString())
                ->get(['id', 'project_id', 'task_id', 'allocation_type', 'allocation_percentage', 'planned_start_date', 'planned_end_date'])
                ->toArray(),
            'working_days' => $this->calendars->workingDaysForEmployee($employee, $from),
        ];
    }

    protected function openTaskEstimatedHours(Employee $employee, Carbon $from, Carbon $to): float
    {
        if (! $employee->user_id) {
            return 0.0;
        }

        $tasks = Task::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $employee->organization_id)
            ->where('assigned_to', $employee->user_id)
            ->where('is_archived', false)
            ->whereNotNull('estimated_hours')
            ->where(function ($query) use ($from, $to): void {
                $query->where(function ($inner) use ($from, $to): void {
                    $inner->whereDate('start_date', '<=', $to->toDateString())
                        ->where(function ($due) use ($from): void {
                            $due->whereNull('due_date')
                                ->orWhereDate('due_date', '>=', $from->toDateString());
                        });
                })->orWhere(function ($inner) use ($from, $to): void {
                    $inner->whereNull('start_date')
                        ->where(function ($due) use ($from, $to): void {
                            $due->whereNull('due_date')
                                ->orWhereBetween('due_date', [$from->toDateString(), $to->toDateString()]);
                        });
                });
            })
            ->with('taskStatus')
            ->get()
            ->filter(fn (Task $task) => $task->isOpen());

        return round((float) $tasks->sum('estimated_hours'), 2);
    }

    protected function approvedLeaveDays(Employee $employee, Carbon $from, Carbon $to): float
    {
        $leaves = $this->leaveService->getApprovedLeaveForDateRange($employee, $from, $to);
        $days = 0.0;

        foreach ($leaves as $leave) {
            $start = $leave->start_date->copy()->max($from);
            $end = $leave->end_date->copy()->min($to);
            $cursor = $start->copy();

            while ($cursor->lte($end)) {
                if ($this->calendars->workingHoursForDay($employee, $cursor) > 0) {
                    $days += $leave->is_half_day ? 0.5 : 1.0;
                }
                $cursor->addDay();
            }
        }

        return round($days, 2);
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
}
