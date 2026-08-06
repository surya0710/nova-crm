<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProjectStatisticsService
{
    public function __construct(
        protected TaskStatisticsService $taskStatistics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forProject(Project $project): array
    {
        $baseStats = $this->taskStatistics->forOrganization(
            $project->organization_id,
            $project->id,
        );

        $tasks = $this->projectTasksQuery($project)
            ->with('taskStatus')
            ->get();

        $closedTasks = $tasks->filter(fn (Task $task) => $task->isClosed());
        $avgDuration = $this->averageTaskDuration($closedTasks);
        $velocity = $this->velocity($project);
        $productivity = $this->teamProductivity($project);

        return [
            'tasks' => [
                'open' => $baseStats['open'],
                'closed' => $baseStats['closed'],
                'overdue' => $baseStats['overdue'],
                'total' => $baseStats['total'],
            ],
            'completion_trends' => $baseStats['trends'],
            'average_task_duration_days' => $avgDuration,
            'velocity' => $velocity,
            'team_productivity' => $productivity,
            'hours' => $baseStats['hours'],
            'progress' => $baseStats['progress'],
        ];
    }

    protected function averageTaskDuration(Collection $closedTasks): ?float
    {
        $durations = $closedTasks
            ->filter(fn (Task $task) => $task->completed_at && $task->created_at)
            ->map(fn (Task $task) => $task->created_at->diffInDays($task->completed_at));

        if ($durations->isEmpty()) {
            return null;
        }

        return round((float) $durations->avg(), 2);
    }

    /**
     * @return array{period_days: int, completed_count: int}
     */
    protected function velocity(Project $project, int $days = 14): array
    {
        $from = Carbon::now()->subDays($days - 1)->startOfDay();

        $completedCount = $this->projectTasksQuery($project)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $from)
            ->count();

        return [
            'period_days' => $days,
            'completed_count' => $completedCount,
        ];
    }

    /**
     * @return list<array{user_id: int|null, name: string, completed_count: int}>
     */
    protected function teamProductivity(Project $project): array
    {
        $rows = $this->projectTasksQuery($project)
            ->whereNotNull('completed_at')
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as completed_count')
            ->groupBy('assigned_to')
            ->orderByDesc('completed_count')
            ->get();

        $users = \App\Models\User::query()
            ->whereIn('id', $rows->pluck('assigned_to'))
            ->get()
            ->keyBy('id');

        return $rows->map(fn ($row) => [
            'user_id' => (int) $row->assigned_to,
            'name' => $users->get($row->assigned_to)?->name ?? __('Unknown'),
            'completed_count' => (int) $row->completed_count,
        ])->all();
    }

    /**
     * @return Builder<Task>
     */
    protected function projectTasksQuery(Project $project): Builder
    {
        return Task::query()
            ->where('organization_id', $project->organization_id)
            ->where(function (Builder $query) use ($project): void {
                $query->where('project_id', $project->id)
                    ->orWhere(function (Builder $inner) use ($project): void {
                        $inner->where('taskable_type', $project->getMorphClass())
                            ->where('taskable_id', $project->id);
                    });
            });
    }
}
