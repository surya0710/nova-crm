<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Task;
use Illuminate\Support\Carbon;

class TaskStatisticsService
{
    /**
     * @return array{
     *     total: int,
     *     open: int,
     *     closed: int,
     *     archived: int,
     *     overdue: int,
     *     progress: array{average: float, remaining: int},
     *     hours: array{estimated: float, actual: float, remaining: float},
     *     trends: array{created: list<array{date: string, count: int}>, completed: list<array{date: string, count: int}>}
     * }
     */
    public function forOrganization(Organization|int $organization, ?int $projectId = null, int $trendDays = 14): array
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        $base = Task::query()
            ->where('organization_id', $organizationId)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId));

        $tasks = (clone $base)->with('taskStatus')->get();

        $open = $tasks->filter(fn (Task $t) => $t->isOpen())->count();
        $closed = $tasks->filter(fn (Task $t) => $t->isClosed())->count();
        $archived = $tasks->filter(fn (Task $t) => $t->isArchived())->count();
        $overdue = $tasks->filter(fn (Task $t) => $t->isOverdue())->count();

        $avgProgress = $tasks->isEmpty()
            ? 0.0
            : round((float) $tasks->avg('completion_percentage'), 2);

        $estimated = (float) $tasks->sum(fn (Task $t) => (float) ($t->estimated_hours ?? 0));
        $actual = (float) $tasks->sum(fn (Task $t) => (float) ($t->actual_hours ?? 0));

        return [
            'total' => $tasks->count(),
            'open' => $open,
            'closed' => $closed,
            'archived' => $archived,
            'overdue' => $overdue,
            'progress' => [
                'average' => $avgProgress,
                'remaining' => max(0, 100 - (int) round($avgProgress)),
            ],
            'hours' => [
                'estimated' => round($estimated, 2),
                'actual' => round($actual, 2),
                'remaining' => round(max(0, $estimated - $actual), 2),
            ],
            'trends' => $this->trends($organizationId, $projectId, $trendDays),
        ];
    }

    /**
     * @return array{
     *     progress: int,
     *     remaining: int,
     *     estimated_hours: float,
     *     actual_hours: float,
     *     logged_minutes: int,
     *     checklist_total: int,
     *     checklist_completed: int,
     *     children_total: int,
     *     children_completed: int,
     *     is_overdue: bool
     * }
     */
    public function forTask(Task $task): array
    {
        $task->loadMissing(['checklists', 'children', 'timeLogs', 'taskStatus']);

        $progress = (int) $task->completion_percentage;
        $loggedMinutes = (int) $task->timeLogs->whereNotNull('end_time')->sum('duration_minutes');
        $estimated = (float) ($task->estimated_hours ?? 0);
        $actual = (float) ($task->actual_hours ?? round($loggedMinutes / 60, 2));

        return [
            'progress' => $progress,
            'remaining' => max(0, 100 - $progress),
            'estimated_hours' => $estimated,
            'actual_hours' => $actual,
            'logged_minutes' => $loggedMinutes,
            'checklist_total' => $task->checklists->count(),
            'checklist_completed' => $task->checklists->where('is_completed', true)->count(),
            'children_total' => $task->children->count(),
            'children_completed' => $task->children->filter(fn (Task $c) => $c->isClosed())->count(),
            'is_overdue' => $task->isOverdue(),
        ];
    }

    /**
     * @return array{created: list<array{date: string, count: int}>, completed: list<array{date: string, count: int}>}
     */
    protected function trends(int $organizationId, ?int $projectId, int $days): array
    {
        $from = Carbon::now()->subDays($days - 1)->startOfDay();

        $created = Task::query()
            ->where('organization_id', $organizationId)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $completed = Task::query()
            ->where('organization_id', $organizationId)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $from)
            ->selectRaw('DATE(completed_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $createdSeries = [];
        $completedSeries = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $createdSeries[] = ['date' => $date, 'count' => (int) ($created[$date] ?? 0)];
            $completedSeries[] = ['date' => $date, 'count' => (int) ($completed[$date] ?? 0)];
        }

        return [
            'created' => $createdSeries,
            'completed' => $completedSeries,
        ];
    }
}
