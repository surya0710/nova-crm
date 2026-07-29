<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ProjectMilestone;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BacklogService
{
    public function __construct(
        protected TaskService $tasks,
        protected SprintService $sprints,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Task>
     */
    public function list(Organization $organization, array $filters = []): Collection
    {
        return Task::query()
            ->where('organization_id', $organization->id)
            ->where('is_archived', false)
            ->when(! empty($filters['project_id']), fn ($q) => $q->where('project_id', (int) $filters['project_id']))
            ->when(
                array_key_exists('sprint_id', $filters) && $filters['sprint_id'] === 'none',
                fn ($q) => $q->whereNull('sprint_id')
            )
            ->when(
                ! empty($filters['sprint_id']) && $filters['sprint_id'] !== 'none',
                fn ($q) => $q->where('sprint_id', (int) $filters['sprint_id'])
            )
            ->when(! empty($filters['unscheduled']), fn ($q) => $q->whereNull('sprint_id'))
            ->with(['assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint'])
            ->orderBy('sort_order')
            ->orderBy('priority_id', 'desc')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int>  $orderedTaskIds
     */
    public function reorder(Organization $organization, array $orderedTaskIds, User $actor): void
    {
        DB::transaction(function () use ($organization, $orderedTaskIds, $actor): void {
            foreach (array_values($orderedTaskIds) as $index => $taskId) {
                $task = Task::query()
                    ->where('organization_id', $organization->id)
                    ->whereKey($taskId)
                    ->first();

                if (! $task) {
                    continue;
                }

                $this->tasks->update($task, ['sort_order' => ($index + 1) * 10], $actor);
            }
        });
    }

    public function moveToSprint(Task $task, ?Sprint $sprint, User $actor): Task
    {
        return $this->sprints->assignTask($task, $sprint, $actor);
    }

    public function moveToMilestone(Task $task, ?ProjectMilestone $milestone, User $actor): Task
    {
        if ($milestone && (int) $milestone->organization_id !== (int) $task->organization_id) {
            throw ValidationException::withMessages([
                'milestone_id' => __('Milestone must belong to the same organization.'),
            ]);
        }

        return $this->tasks->update($task, [
            'milestone_id' => $milestone?->id,
        ], $actor);
    }

    /**
     * @param  list<int>  $taskIds
     */
    public function bulkAssign(Organization $organization, array $taskIds, ?int $userId, User $actor): int
    {
        $count = 0;

        foreach ($this->tasksForIds($organization, $taskIds) as $task) {
            $assignee = $userId ? User::query()->find($userId) : null;
            $this->tasks->assign($task, $assignee, $actor);
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<int>  $taskIds
     */
    public function bulkPriority(Organization $organization, array $taskIds, string $priority, User $actor): int
    {
        $count = 0;

        foreach ($this->tasksForIds($organization, $taskIds) as $task) {
            $this->tasks->update($task, ['priority' => $priority], $actor);
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<int>  $taskIds
     * @return Collection<int, Task>
     */
    protected function tasksForIds(Organization $organization, array $taskIds): Collection
    {
        return Task::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $taskIds)
            ->get();
    }
}
