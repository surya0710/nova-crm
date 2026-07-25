<?php

namespace App\Services;

use App\Events\TimelineUpdated;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ResourceAllocation;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;

class TimelineService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Project $project): array
    {
        $project->loadMissing(['milestones', 'status']);

        $tasks = $this->projectTasksQuery($project)
            ->with(['predecessorDependencies.predecessor', 'successorDependencies.successor'])
            ->orderBy('sort_order')
            ->get();

        $taskIds = $tasks->pluck('id');

        $dependencies = TaskDependency::query()
            ->where('organization_id', $project->organization_id)
            ->where(function (Builder $query) use ($taskIds): void {
                $query->whereIn('predecessor_task_id', $taskIds)
                    ->orWhereIn('successor_task_id', $taskIds);
            })
            ->get();

        $allocations = ResourceAllocation::query()
            ->where('organization_id', $project->organization_id)
            ->where('project_id', $project->id)
            ->with(['employee'])
            ->get();

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'start_date' => $project->start_date?->toDateString(),
                'planned_end_date' => $project->planned_end_date?->toDateString(),
                'actual_end_date' => $project->actual_end_date?->toDateString(),
                'completion_percentage' => (int) $project->completion_percentage,
            ],
            'milestones' => $project->milestones->map(fn (ProjectMilestone $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'due_date' => $m->due_date?->toDateString(),
                'status' => $m->status,
                'sequence' => $m->sequence,
            ])->all(),
            'tasks' => $tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'start_date' => $task->start_date?->toDateString(),
                'due_date' => $task->due_date?->toDateString() ?? $task->due_at?->toDateString(),
                'completion_percentage' => (int) $task->completion_percentage,
                'status' => $task->status,
                'milestone_id' => $task->milestone_id,
            ])->all(),
            'dependencies' => $dependencies->map(fn (TaskDependency $dep) => [
                'id' => $dep->id,
                'predecessor_task_id' => $dep->predecessor_task_id,
                'successor_task_id' => $dep->successor_task_id,
                'dependency_type' => $dep->dependency_type,
            ])->all(),
            'resource_allocations' => $allocations->map(fn (ResourceAllocation $alloc) => [
                'id' => $alloc->id,
                'employee_id' => $alloc->employee_id,
                'employee_name' => $alloc->employee?->full_name ?? $alloc->employee?->name ?? null,
                'task_id' => $alloc->task_id,
                'allocation_percentage' => $alloc->allocation_percentage,
                'planned_start_date' => $alloc->planned_start_date?->toDateString(),
                'planned_end_date' => $alloc->planned_end_date?->toDateString(),
                'planned_hours' => $alloc->planned_hours,
            ])->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function gantt(Project $project): array
    {
        $items = [];
        $colors = [
            'milestone' => '#4f46e5',
            'task' => '#0ea5e9',
            'project' => '#22c55e',
        ];

        if ($project->start_date && $project->planned_end_date) {
            $items[] = [
                'id' => 'project-'.$project->id,
                'type' => 'project',
                'name' => $project->name,
                'start' => $project->start_date->toDateString(),
                'end' => $project->planned_end_date->toDateString(),
                'progress' => (int) $project->completion_percentage,
                'status' => $project->status?->slug ?? 'active',
                'dependencies' => [],
                'color' => $colors['project'],
            ];
        }

        foreach ($project->milestones as $milestone) {
            $start = $project->start_date?->toDateString()
                ?? $milestone->due_date?->copy()->subDays(7)->toDateString()
                ?? now()->toDateString();
            $end = $milestone->due_date?->toDateString() ?? $start;

            $items[] = [
                'id' => 'milestone-'.$milestone->id,
                'type' => 'milestone',
                'name' => $milestone->name,
                'start' => $start,
                'end' => $end,
                'progress' => $milestone->isCompleted() ? 100 : 0,
                'status' => $milestone->status,
                'dependencies' => [],
                'color' => $colors['milestone'],
            ];
        }

        $tasks = $this->projectTasksQuery($project)
            ->with('predecessorDependencies')
            ->get();

        foreach ($tasks as $task) {
            $start = $task->start_date?->toDateString()
                ?? $task->created_at?->toDateString()
                ?? now()->toDateString();
            $end = $task->due_date?->toDateString()
                ?? $task->due_at?->toDateString()
                ?? $start;

            $items[] = [
                'id' => 'task-'.$task->id,
                'type' => 'task',
                'name' => $task->title,
                'start' => $start,
                'end' => $end,
                'progress' => (int) $task->completion_percentage,
                'status' => $task->status,
                'dependencies' => $task->predecessorDependencies
                    ->pluck('predecessor_task_id')
                    ->map(fn ($id) => 'task-'.$id)
                    ->values()
                    ->all(),
                'color' => $colors['task'],
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function criticalMilestones(Project $project): array
    {
        return $project->milestones()
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->orderBy('due_date')
            ->orderBy('sequence')
            ->get()
            ->map(fn (ProjectMilestone $milestone) => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'due_date' => $milestone->due_date?->toDateString(),
                'status' => $milestone->status,
                'sequence' => $milestone->sequence,
            ])
            ->all();
    }

    public function publishUpdate(Project $project, ?User $actor = null): array
    {
        $timeline = $this->build($project);

        $runtime = app(WorkflowRuntimeContext::class);
        event(TimelineUpdated::forModel(
            $project->fresh(),
            [
                'actor_id' => $actor?->id,
                'milestone_count' => count($timeline['milestones']),
                'task_count' => count($timeline['tasks']),
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $timeline;
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
