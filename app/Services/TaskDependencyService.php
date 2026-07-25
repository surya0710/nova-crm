<?php

namespace App\Services;

use App\Events\DependencyCreated;
use App\Events\DependencyRemoved;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TaskDependencyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Task $predecessor, Task $successor, array $data, User $actor): TaskDependency
    {
        if ($predecessor->organization_id !== $successor->organization_id) {
            throw ValidationException::withMessages([
                'dependency' => __('Tasks must belong to the same organization.'),
            ]);
        }

        if ($predecessor->id === $successor->id) {
            throw ValidationException::withMessages([
                'dependency' => __('A task cannot depend on itself.'),
            ]);
        }

        $type = $data['dependency_type'] ?? 'finish_to_start';
        $this->validateType($type);

        if ($this->wouldCreateCycle($predecessor->id, $successor->id, (int) $predecessor->organization_id)) {
            throw ValidationException::withMessages([
                'dependency' => __('This dependency would create a circular relationship.'),
            ]);
        }

        $existing = TaskDependency::query()
            ->where('predecessor_task_id', $predecessor->id)
            ->where('successor_task_id', $successor->id)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'dependency' => __('This dependency already exists.'),
            ]);
        }

        $dependency = TaskDependency::query()->create([
            'organization_id' => $predecessor->organization_id,
            'predecessor_task_id' => $predecessor->id,
            'successor_task_id' => $successor->id,
            'dependency_type' => $type,
        ]);

        $runtime = app(WorkflowRuntimeContext::class);
        event(DependencyCreated::forModel(
            $dependency,
            [
                'actor_id' => $actor->id,
                'predecessor_task_id' => $predecessor->id,
                'successor_task_id' => $successor->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $dependency->fresh(['predecessor', 'successor']);
    }

    public function delete(TaskDependency $dependency, User $actor): void
    {
        $payload = [
            'actor_id' => $actor->id,
            'predecessor_task_id' => $dependency->predecessor_task_id,
            'successor_task_id' => $dependency->successor_task_id,
        ];

        $runtime = app(WorkflowRuntimeContext::class);
        event(DependencyRemoved::forModel(
            $dependency,
            $payload,
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $dependency->delete();
    }

    /**
     * @return array{nodes: list<int>, edges: list<array{from: int, to: int, type: string}>}
     */
    public function dependencyGraph(int $organizationId, ?int $projectId = null): array
    {
        $taskQuery = Task::query()
            ->where('organization_id', $organizationId)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId));

        $taskIds = $taskQuery->pluck('id');

        $edges = TaskDependency::query()
            ->where('organization_id', $organizationId)
            ->whereIn('predecessor_task_id', $taskIds)
            ->whereIn('successor_task_id', $taskIds)
            ->get()
            ->map(fn (TaskDependency $d) => [
                'from' => (int) $d->predecessor_task_id,
                'to' => (int) $d->successor_task_id,
                'type' => $d->dependency_type,
            ])
            ->values()
            ->all();

        return [
            'nodes' => $taskIds->map(fn ($id) => (int) $id)->values()->all(),
            'edges' => $edges,
        ];
    }

    public function wouldCreateCycle(int $predecessorId, int $successorId, int $organizationId): bool
    {
        // Adding edge predecessor -> successor creates a cycle if successor can already reach predecessor.
        return $this->canReach($successorId, $predecessorId, $organizationId);
    }

    protected function canReach(int $fromId, int $targetId, int $organizationId): bool
    {
        $adjacency = $this->adjacency($organizationId);
        $visited = [];
        $stack = [$fromId];

        while ($stack !== []) {
            $current = array_pop($stack);

            if ($current === $targetId) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            foreach ($adjacency[$current] ?? [] as $next) {
                if (! isset($visited[$next])) {
                    $stack[] = $next;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, list<int>>
     */
    protected function adjacency(int $organizationId): array
    {
        /** @var Collection<int, TaskDependency> $deps */
        $deps = TaskDependency::query()
            ->where('organization_id', $organizationId)
            ->get(['predecessor_task_id', 'successor_task_id']);

        $map = [];

        foreach ($deps as $dep) {
            $from = (int) $dep->predecessor_task_id;
            $to = (int) $dep->successor_task_id;
            $map[$from][] = $to;
        }

        return $map;
    }

    protected function validateType(string $type): void
    {
        if (! array_key_exists($type, config('tasks.dependency_types', []))) {
            throw ValidationException::withMessages([
                'dependency_type' => __('Invalid dependency type.'),
            ]);
        }
    }
}
