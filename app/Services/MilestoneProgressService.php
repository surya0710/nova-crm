<?php

namespace App\Services;

use App\Events\MilestoneDelayed;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;

class MilestoneProgressService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forProject(Project $project): array
    {
        return $project->milestones()
            ->orderBy('sequence')
            ->get()
            ->map(fn (ProjectMilestone $milestone) => array_merge(
                ['milestone_id' => $milestone->id, 'name' => $milestone->name],
                $this->forMilestone($milestone),
            ))
            ->all();
    }

    /**
     * @return array{
     *     planned_progress: int,
     *     actual_progress: int,
     *     progress_percentage: int,
     *     delay_days: int,
     *     tasks_total: int,
     *     tasks_completed: int,
     *     remaining_tasks: int,
     *     target_date: string|null,
     *     is_delayed: bool,
     *     is_overdue: bool
     * }
     */
    public function forMilestone(ProjectMilestone $milestone): array
    {
        $milestone->loadMissing('project');
        $project = $milestone->project;

        $planned = $project ? $this->plannedProgress($project, $milestone) : 0;
        $actual = $this->actualProgress($milestone);
        $taskStats = $this->taskCounts($milestone);
        $isDelayed = $this->isDelayed($milestone);
        $delayDays = $this->delayDays($milestone);

        if ($isDelayed && ! $this->wasDelayNotified($milestone)) {
            $this->fireMilestoneDelayed($milestone);
        }

        return [
            'planned_progress' => $planned,
            'actual_progress' => $actual,
            'progress_percentage' => $actual,
            'delay_days' => $delayDays,
            'tasks_total' => $taskStats['total'],
            'tasks_completed' => $taskStats['completed'],
            'remaining_tasks' => $taskStats['remaining'],
            'target_date' => $milestone->due_date?->toDateString(),
            'is_delayed' => $isDelayed,
            'is_overdue' => $isDelayed,
        ];
    }

    protected function plannedProgress(Project $project, ProjectMilestone $milestone): int
    {
        if (! $milestone->due_date || ! $project->start_date) {
            return 0;
        }

        $start = $project->start_date->startOfDay();
        $due = $milestone->due_date->startOfDay();
        $today = now()->startOfDay();

        $totalDays = max(1, $start->diffInDays($due));

        if ($today->lte($start)) {
            return 0;
        }

        if ($today->gte($due)) {
            return 100;
        }

        $elapsed = $start->diffInDays($today);

        return (int) min(100, max(0, round(($elapsed / $totalDays) * 100)));
    }

    protected function actualProgress(ProjectMilestone $milestone): int
    {
        if ($milestone->isCompleted()) {
            return 100;
        }

        $tasks = $this->milestoneTasks($milestone)->get(['completion_percentage']);

        if ($tasks->isEmpty()) {
            return 0;
        }

        return (int) round((float) $tasks->avg('completion_percentage'));
    }

    /**
     * @return Builder<Task>
     */
    protected function milestoneTasks(ProjectMilestone $milestone): Builder
    {
        $project = $milestone->project;

        $query = Task::query()
            ->where('organization_id', $milestone->organization_id);

        if ($this->tasksHaveMilestoneColumn()) {
            return $query->where('milestone_id', $milestone->id);
        }

        if (! $project || ! $milestone->due_date) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inner) use ($project): void {
            $inner->where('project_id', $project->id)
                ->orWhere(function (Builder $morph) use ($project): void {
                    $morph->where('taskable_type', $project->getMorphClass())
                        ->where('taskable_id', $project->id);
                });
        })->where(function (Builder $inner) use ($milestone, $project): void {
            if ($project->start_date) {
                $inner->whereDate('created_at', '>=', $project->start_date)
                    ->whereDate('created_at', '<=', $milestone->due_date);
            } else {
                $inner->whereDate('created_at', '<=', $milestone->due_date);
            }
        });
    }

    /**
     * @return array{total: int, completed: int, remaining: int}
     */
    protected function taskCounts(ProjectMilestone $milestone): array
    {
        $tasks = $this->milestoneTasks($milestone)->with('taskStatus')->get();
        $completed = $tasks->filter(fn (Task $task) => $task->isClosed())->count();
        $remaining = $tasks->filter(fn (Task $task) => $task->isOpen())->count();

        return [
            'total' => $tasks->count(),
            'completed' => $completed,
            'remaining' => $remaining,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Task>
     */
    protected function remainingTasks(ProjectMilestone $milestone): \Illuminate\Support\Collection
    {
        return $this->milestoneTasks($milestone)
            ->with('taskStatus')
            ->get()
            ->filter(fn (Task $task) => $task->isOpen())
            ->values();
    }

    protected function isDelayed(ProjectMilestone $milestone): bool
    {
        if ($milestone->isCompleted() || $milestone->status === 'cancelled') {
            return false;
        }

        return $milestone->due_date !== null
            && $milestone->due_date->isPast();
    }

    protected function delayDays(ProjectMilestone $milestone): int
    {
        if (! $this->isDelayed($milestone) || ! $milestone->due_date) {
            return 0;
        }

        return (int) $milestone->due_date->startOfDay()->diffInDays(now()->startOfDay());
    }

    protected function tasksHaveMilestoneColumn(): bool
    {
        return (new Task)->getConnection()
            ->getSchemaBuilder()
            ->hasColumn((new Task)->getTable(), 'milestone_id');
    }

    protected function wasDelayNotified(ProjectMilestone $milestone): bool
    {
        $project = $milestone->project;

        if (! $project) {
            return false;
        }

        $notified = $project->settings['delayed_milestones_notified'] ?? [];

        return in_array($milestone->id, $notified, true);
    }

    protected function fireMilestoneDelayed(ProjectMilestone $milestone): void
    {
        $runtime = app(WorkflowRuntimeContext::class);

        event(MilestoneDelayed::forModel(
            $milestone->fresh(),
            [
                'project_id' => $milestone->project_id,
                'delay_days' => $this->delayDays($milestone),
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $project = $milestone->project;

        if (! $project) {
            return;
        }

        $settings = $project->settings ?? [];
        $notified = $settings['delayed_milestones_notified'] ?? [];
        $notified[] = $milestone->id;
        $settings['delayed_milestones_notified'] = array_values(array_unique($notified));

        $project->update(['settings' => $settings]);
    }
}
