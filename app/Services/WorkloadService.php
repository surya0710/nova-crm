<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\ProjectMember;
use App\Models\ResourceAllocation;
use App\Models\Task;
use App\Models\TaskTimeLog;
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
     * Manager-facing resource allocation dashboard rows (Release 1.2.2).
     *
     * @param  array{project_id?: int|null, department_id?: int|null, branch_id?: int|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function allocationDashboard(Organization $organization, Carbon $from, Carbon $to, array $filters = []): array
    {
        $employees = $this->activeEmployees($organization);

        if (! empty($filters['department_id'])) {
            $employees = $employees->where('department_id', (int) $filters['department_id'])->values();
        }

        if (! empty($filters['branch_id'])) {
            $employees = $employees->where('branch_id', (int) $filters['branch_id'])->values();
        }

        $userIds = $employees->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->values();
        $projectFilter = ! empty($filters['project_id']) ? (int) $filters['project_id'] : null;

        $activeProjectsByUser = ProjectMember::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->whereIn('user_id', $userIds)
            ->when($projectFilter, fn ($q) => $q->where('project_id', $projectFilter))
            ->selectRaw('user_id, COUNT(DISTINCT project_id) as project_count')
            ->groupBy('user_id')
            ->pluck('project_count', 'user_id');

        $openTasks = Task::query()
            ->where('organization_id', $organization->id)
            ->where('is_archived', false)
            ->whereIn('assigned_to', $userIds)
            ->when($projectFilter, fn ($q) => $q->where('project_id', $projectFilter))
            ->with('taskStatus')
            ->get()
            ->filter(fn (Task $task) => $task->isOpen())
            ->groupBy('assigned_to');

        $loggedMinutesByUser = TaskTimeLog::query()
            ->where('organization_id', $organization->id)
            ->whereIn('user_id', $userIds)
            ->whereNotNull('duration_minutes')
            ->whereBetween('start_time', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('user_id, SUM(duration_minutes) as minutes')
            ->groupBy('user_id')
            ->pluck('minutes', 'user_id');

        $rows = [];

        foreach ($employees as $employee) {
            $workload = $this->calculateForEmployee($employee, $from, $to);
            $userId = $employee->user_id ? (int) $employee->user_id : null;
            $tasks = $userId ? ($openTasks->get($userId) ?? collect()) : collect();

            if ($projectFilter && $userId && ! $activeProjectsByUser->has($userId) && $tasks->isEmpty()) {
                continue;
            }

            $estimated = round((float) $tasks->sum(fn (Task $t) => (float) ($t->estimated_hours ?? 0)), 2);
            $loggedFromTasks = round((float) $tasks->sum(fn (Task $t) => (float) ($t->actual_hours ?? 0)), 2);
            $loggedFromLogs = $userId
                ? round(((float) ($loggedMinutesByUser[$userId] ?? 0)) / 60, 2)
                : 0.0;
            $logged = max($loggedFromTasks, $loggedFromLogs);
            $remaining = round(max(0, $estimated - $logged), 2);
            $status = $workload['status'];

            $rows[] = [
                ...$workload,
                'employee_id' => (int) $employee->id,
                'employee_name' => $employee->full_name,
                'user_id' => $userId,
                'department_id' => $employee->department_id,
                'branch_id' => $employee->branch_id ?? null,
                'active_projects' => $userId ? (int) ($activeProjectsByUser[$userId] ?? 0) : 0,
                'active_tasks' => $tasks->count(),
                'estimated_hours' => $estimated,
                'logged_hours' => $logged,
                'remaining_hours' => $remaining,
                'capacity_percentage' => $workload['utilization'],
                'overallocated' => $status === 'overallocated',
                'display_status' => $this->displayStatusLabel($status),
            ];
        }

        return $rows;
    }

    /**
     * Per-employee workload timeline: current/future tasks, leave, free capacity.
     *
     * @return array<string, mixed>
     */
    public function employeeTimeline(Employee $employee, Carbon $from, Carbon $to): array
    {
        $workload = $this->calculateForEmployee($employee, $from, $to);
        $userId = $employee->user_id ? (int) $employee->user_id : null;
        $today = now()->startOfDay();

        $tasks = $userId
            ? Task::query()
                ->where('organization_id', $employee->organization_id)
                ->where('assigned_to', $userId)
                ->where('is_archived', false)
                ->with(['project:id,name', 'taskStatus'])
                ->orderBy('due_date')
                ->get()
                ->filter(fn (Task $task) => $task->isOpen())
                ->values()
            : collect();

        $current = $tasks->filter(function (Task $task) use ($today) {
            $due = $task->due_date ?? $task->due_at;
            $start = $task->start_date;

            if ($start && $start->gt($today)) {
                return false;
            }

            if ($due === null) {
                return true;
            }

            $dueDay = $due instanceof \Carbon\CarbonInterface
                ? $due->copy()->startOfDay()
                : Carbon::parse($due)->startOfDay();

            return $dueDay->lte($today->copy()->addDays(7));
        })->values();

        $future = $tasks->filter(function (Task $task) use ($current) {
            return ! $current->contains('id', $task->id);
        })->values();

        $leave = $this->leaveService->getApprovedLeaveForDateRange($employee, $from, $to)
            ->map(fn ($leave) => [
                'id' => $leave->id,
                'start_date' => $leave->start_date->toDateString(),
                'end_date' => $leave->end_date->toDateString(),
                'type' => $leave->leaveType?->name,
                'is_half_day' => (bool) $leave->is_half_day,
            ])
            ->values()
            ->all();

        return [
            'employee_id' => (int) $employee->id,
            'employee_name' => $employee->full_name,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'capacity_percentage' => $workload['utilization'],
            'available_hours' => $workload['available'],
            'allocated_hours' => $workload['allocated'],
            'free_capacity_hours' => round(max(0, $workload['available'] - $workload['allocated']), 2),
            'status' => $workload['status'],
            'display_status' => $this->displayStatusLabel($workload['status']),
            'current_tasks' => $current->map(fn (Task $t) => $this->taskTimelineRow($t))->all(),
            'future_tasks' => $future->map(fn (Task $t) => $this->taskTimelineRow($t))->all(),
            'leave' => $leave,
            'days' => $workload['days'],
        ];
    }

    /**
     * Team charts for daily/weekly/monthly workload views.
     *
     * @param  array{project_id?: int|null, department_id?: int|null, branch_id?: int|null}  $filters
     * @return array<string, mixed>
     */
    public function teamWorkloadCharts(Organization $organization, Carbon $from, Carbon $to, array $filters = []): array
    {
        $rows = $this->allocationDashboard($organization, $from, $to, $filters);

        $upcomingDeadlines = Task::query()
            ->where('organization_id', $organization->id)
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->with('taskStatus')
            ->get()
            ->filter(fn (Task $task) => $task->isOpen())
            ->take(20)
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date?->toDateString(),
                'assignee_id' => $task->assigned_to,
                'project_id' => $task->project_id,
            ])
            ->values()
            ->all();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'tasks_per_employee' => collect($rows)->map(fn ($r) => [
                'employee' => $r['employee_name'],
                'tasks' => $r['active_tasks'],
            ])->all(),
            'hours_per_employee' => collect($rows)->map(fn ($r) => [
                'employee' => $r['employee_name'],
                'estimated' => $r['estimated_hours'],
                'logged' => $r['logged_hours'],
                'allocated' => $r['allocated'],
            ])->all(),
            'remaining_workload' => collect($rows)->map(fn ($r) => [
                'employee' => $r['employee_name'],
                'remaining_hours' => $r['remaining_hours'],
                'free_capacity' => round(max(0, $r['available'] - $r['allocated']), 2),
            ])->all(),
            'upcoming_deadlines' => $upcomingDeadlines,
            'rows' => $rows,
        ];
    }

    public function displayStatusLabel(string $status): string
    {
        return match ($status) {
            'overallocated' => __('Overallocated'),
            'underutilized' => __('Available'),
            default => __('Healthy'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function taskTimelineRow(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'project' => $task->project?->name,
            'due_date' => $task->due_date?->toDateString() ?? $task->due_at?->toDateString(),
            'estimated_hours' => (float) ($task->estimated_hours ?? 0),
            'status' => $task->taskStatus?->name ?? $task->status,
            'url' => route('tasks.show', $task),
        ];
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
