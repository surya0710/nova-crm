<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Organization;
use App\Models\ProjectMilestone;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Unified project planning calendar (Release 1.2.2).
 * Aggregates tasks, milestones, deadlines, leave, and holidays — no duplicate date logic.
 */
class ProjectCalendarService
{
    /**
     * @param  array{
     *     project_id?: int|null,
     *     employee_id?: int|null,
     *     user_id?: int|null,
     *     status?: string|null,
     *     priority?: string|null,
     *     view?: string,
     *     year?: int,
     *     month?: int,
     *     from?: string|null,
     *     to?: string|null
     * }  $filters
     * @return array<string, mixed>
     */
    public function build(Organization $organization, array $filters = []): array
    {
        $view = $filters['view'] ?? 'month';
        [$from, $to] = $this->resolveRange($filters, $view);

        $events = collect()
            ->merge($this->taskEvents($organization, $from, $to, $filters))
            ->merge($this->milestoneEvents($organization, $from, $to, $filters))
            ->merge($this->holidayEvents($organization, $from, $to))
            ->merge($this->leaveEvents($organization, $from, $to, $filters))
            ->sortBy('starts_at')
            ->values();

        return [
            'view' => $view,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'year' => (int) $from->year,
            'month' => (int) $from->month,
            'filters' => $filters,
            'events' => $events->all(),
            'days' => $this->groupByDay($events, $from, $to),
            'legend' => $this->legend(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveRange(array $filters, string $view): array
    {
        if (! empty($filters['from']) && ! empty($filters['to'])) {
            return [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->endOfDay(),
            ];
        }

        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);

        return match ($view) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'agenda' => [now()->startOfDay(), now()->addDays(30)->endOfDay()],
            default => [
                Carbon::create($year, $month, 1)->startOfMonth(),
                Carbon::create($year, $month, 1)->endOfMonth(),
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function taskEvents(Organization $organization, Carbon $from, Carbon $to, array $filters): Collection
    {
        $query = Task::query()
            ->where('organization_id', $organization->id)
            ->where('is_archived', false)
            ->with(['assignee:id,name', 'project:id,name', 'taskStatus:id,name,slug'])
            ->where(function ($q) use ($from, $to): void {
                $q->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('due_at', [$from, $to])
                    ->orWhereBetween('start_date', [$from->toDateString(), $to->toDateString()]);
            });

        if (! empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('assigned_to', (int) $filters['user_id']);
        }

        if (! empty($filters['employee_id'])) {
            $employee = Employee::query()->find((int) $filters['employee_id']);
            if ($employee?->user_id) {
                $query->where('assigned_to', $employee->user_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->get()->map(function (Task $task) {
            $starts = $task->due_at
                ?? ($task->due_date?->copy()->setTime(9, 0))
                ?? ($task->start_date?->copy()->setTime(9, 0));

            return [
                'id' => 'task-'.$task->id,
                'type' => 'task',
                'title' => $task->title,
                'starts_at' => $starts?->toIso8601String(),
                'date' => $starts?->toDateString(),
                'project_id' => $task->project_id,
                'project_name' => $task->project?->name,
                'task_id' => $task->id,
                'status' => $task->taskStatus?->name ?? $task->status,
                'priority' => $task->priority,
                'assignee' => $task->assignee?->name,
                'url' => route('tasks.show', $task),
                'color' => 'blue',
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function milestoneEvents(Organization $organization, Carbon $from, Carbon $to, array $filters): Collection
    {
        $query = ProjectMilestone::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->with('project:id,name');

        if (! empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        return $query->get()->map(fn (ProjectMilestone $milestone) => [
            'id' => 'milestone-'.$milestone->id,
            'type' => 'milestone',
            'title' => $milestone->name,
            'starts_at' => $milestone->due_date->copy()->startOfDay()->toIso8601String(),
            'date' => $milestone->due_date->toDateString(),
            'project_id' => $milestone->project_id,
            'project_name' => $milestone->project?->name,
            'milestone_id' => $milestone->id,
            'status' => $milestone->status,
            'url' => $milestone->project_id ? route('projects.milestones.index', $milestone->project_id) : null,
            'color' => 'amber',
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function holidayEvents(Organization $organization, Carbon $from, Carbon $to): Collection
    {
        return Holiday::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('holiday_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(fn (Holiday $holiday) => [
                'id' => 'holiday-'.$holiday->id,
                'type' => 'holiday',
                'title' => $holiday->name,
                'starts_at' => $holiday->holiday_date->copy()->startOfDay()->toIso8601String(),
                'date' => $holiday->holiday_date->toDateString(),
                'status' => $holiday->is_optional ? 'optional' : 'public',
                'color' => 'orange',
                'url' => null,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function leaveEvents(Organization $organization, Carbon $from, Carbon $to, array $filters): Collection
    {
        $query = LeaveApplication::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($from, $to): void {
                $q->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('end_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(function ($inner) use ($from, $to): void {
                        $inner->where('start_date', '<=', $from->toDateString())
                            ->where('end_date', '>=', $to->toDateString());
                    });
            })
            ->with(['employee:id,first_name,last_name,user_id', 'leaveType:id,name']);

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('user_id', (int) $filters['user_id']));
        }

        $events = collect();

        foreach ($query->get() as $leave) {
            $cursor = $leave->start_date->copy()->max($from->copy()->startOfDay());
            $end = $leave->end_date->copy()->min($to->copy()->startOfDay());

            while ($cursor->lte($end)) {
                $events->push([
                    'id' => 'leave-'.$leave->id.'-'.$cursor->toDateString(),
                    'type' => 'leave',
                    'title' => ($leave->employee?->full_name ?? __('Employee')).' · '.($leave->leaveType?->name ?? __('Leave')),
                    'starts_at' => $cursor->copy()->startOfDay()->toIso8601String(),
                    'date' => $cursor->toDateString(),
                    'employee_id' => $leave->employee_id,
                    'status' => $leave->status,
                    'color' => 'sky',
                    'url' => null,
                ]);
                $cursor->addDay();
            }
        }

        return $events;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $events
     * @return array<string, list<array<string, mixed>>>
     */
    protected function groupByDay(Collection $events, Carbon $from, Carbon $to): array
    {
        $days = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $days[$key] = $events->where('date', $key)->values()->all();
            $cursor->addDay();
        }

        return $days;
    }

    /** @return list<array{key: string, label: string, color: string}> */
    protected function legend(): array
    {
        return [
            ['key' => 'task', 'label' => __('Tasks'), 'color' => 'blue'],
            ['key' => 'milestone', 'label' => __('Milestones'), 'color' => 'amber'],
            ['key' => 'holiday', 'label' => __('Holidays'), 'color' => 'orange'],
            ['key' => 'leave', 'label' => __('Team Leave'), 'color' => 'sky'],
        ];
    }
}
