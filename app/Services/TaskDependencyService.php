<?php

namespace App\Services;

use App\Events\DependencyCreated;
use App\Events\DependencyRemoved;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
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

        $dependency = $dependency->fresh(['predecessor', 'successor.project.manager', 'successor.project.owner', 'successor.assignee']);
        $this->notifyBlockedDependency($predecessor, $successor->fresh(['assignee', 'project.manager', 'project.owner']), $actor, $type);

        return $dependency;
    }

    protected function notifyBlockedDependency(Task $predecessor, Task $successor, User $actor, string $type): void
    {
        if (! $predecessor->isOpen()) {
            return;
        }

        $blockingTypes = config('tasks.blocking_dependency_types', ['finish_to_start']);

        if (! in_array($type, $blockingTypes, true)) {
            return;
        }

        $recipients = collect([$successor->assignee, $successor->project?->manager, $successor->project?->owner])
            ->filter()
            ->unique('id');

        foreach ($recipients as $recipient) {
            if ($recipient->id === $actor->id) {
                continue;
            }

            $recipient->notify(new CrmNotification(
                title: __('Task blocked by dependency'),
                message: __(':task is blocked by :blocker.', [
                    'task' => $successor->title,
                    'blocker' => $predecessor->title,
                ]),
                actionUrl: Route::has('tasks.show') ? route('tasks.show', $successor) : null,
                organizationId: (int) $successor->organization_id,
            ));
        }
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

    /**
     * Incomplete predecessors that currently block this task (finish-to-start by default).
     *
     * @return Collection<int, Task>
     */
    public function blockingPredecessors(Task $task): Collection
    {
        $blockingTypes = config('tasks.blocking_dependency_types', ['finish_to_start']);

        return TaskDependency::query()
            ->where('organization_id', $task->organization_id)
            ->where('successor_task_id', $task->id)
            ->whereIn('dependency_type', $blockingTypes)
            ->with(['predecessor.assignee', 'predecessor.taskStatus'])
            ->get()
            ->map(fn (TaskDependency $dep) => $dep->predecessor)
            ->filter(fn (?Task $predecessor) => $predecessor && $predecessor->isOpen())
            ->values();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function blockedBySummary(Task $task): array
    {
        return $this->blockingPredecessors($task)
            ->map(fn (Task $blocker) => [
                'task_id' => $blocker->id,
                'title' => $blocker->title,
                'assigned_to' => $blocker->assignee?->name,
                'status' => $blocker->taskStatus?->name ?? $blocker->status,
                'url' => route('tasks.show', $blocker),
            ])
            ->all();
    }

    /**
     * Visual chain: Task A → Blocks → Task B → Blocks → Task C
     *
     * @return list<array{task_id: int, title: string, status: string|null}>
     */
    public function dependencyChain(Task $task, int $maxDepth = 8): array
    {
        $chain = [[
            'task_id' => $task->id,
            'title' => $task->title,
            'status' => $task->taskStatus?->name ?? $task->status,
        ]];

        $cursor = $task;
        $guard = 0;

        while ($guard < $maxDepth) {
            $next = TaskDependency::query()
                ->where('organization_id', $cursor->organization_id)
                ->where('predecessor_task_id', $cursor->id)
                ->with('successor.taskStatus')
                ->orderBy('id')
                ->first();

            if (! $next?->successor) {
                break;
            }

            $cursor = $next->successor;
            $chain[] = [
                'task_id' => $cursor->id,
                'title' => $cursor->title,
                'status' => $cursor->taskStatus?->name ?? $cursor->status,
            ];
            $guard++;
        }

        return $chain;
    }

    public function assertCanComplete(Task $task): void
    {
        if (! config('tasks.enforce_dependency_blocking', true)) {
            return;
        }

        $blockers = $this->blockingPredecessors($task);

        if ($blockers->isEmpty()) {
            return;
        }

        $names = $blockers->pluck('title')->implode(', ');

        throw ValidationException::withMessages([
            'status' => __('This task is blocked by incomplete dependencies: :tasks', [
                'tasks' => $names,
            ]),
        ]);
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
