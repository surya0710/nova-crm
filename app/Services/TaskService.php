<?php

namespace App\Services;

use App\Events\TaskArchived;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskReassigned;
use App\Events\TaskRestored;
use App\Events\TaskStarted;
use App\Events\TaskUpdated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaskService
{
    protected ?TaskDefaultsService $defaults = null;

    protected ?MetadataEntityFormService $metadataForms = null;

    public function __construct(
        ?TaskDefaultsService $defaults = null,
        ?MetadataEntityFormService $metadataForms = null,
    ) {
        $this->defaults = $defaults;
        $this->metadataForms = $metadataForms;
    }

    public function createFor(Model $subject, array $data, User $actor): Task
    {
        $this->assertTenant($subject, $actor);
        $this->validateLegacyData($data);

        $organization = $subject->organization;
        $assigneeId = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;

        if ($assigneeId && ! $organization->users()->whereKey($assigneeId)->exists()) {
            throw ValidationException::withMessages(['assigned_to' => 'The assignee is not an organization member.']);
        }

        $this->ensureDefaults($organization);

        $status = $data['status'] ?? 'pending';
        $priority = $data['priority'] ?? 'medium';
        $statusId = $data['status_id'] ?? $this->resolveStatusIdFromLegacy($organization, $status);
        $priorityId = $data['priority_id'] ?? $this->resolvePriorityIdFromLegacy($organization, $priority);

        $payload = [
            'organization_id' => $subject->organization_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $status,
            'priority' => $priority,
            'status_id' => $statusId,
            'priority_id' => $priorityId,
            'due_at' => $data['due_at'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'assigned_to' => $assigneeId,
            'assigned_by' => $assigneeId ? $actor->id : null,
            'taskable_type' => $subject->getMorphClass(),
            'taskable_id' => $subject->getKey(),
            'created_by' => $actor->id,
            'completed_at' => $status === 'completed' ? now() : null,
            'project_id' => $data['project_id'] ?? ($subject instanceof Project ? $subject->id : null),
            'parent_task_id' => $data['parent_task_id'] ?? null,
            'milestone_id' => $data['milestone_id'] ?? null,
        ];

        $this->syncLegacyFromCatalogIds($payload, $organization);

        $task = Task::query()->create($payload);
        $task = $task->fresh(['assignee', 'taskStatus', 'taskPriority']);

        event(TaskCreated::forModel($task, ['actor_id' => $actor->id]));

        if ($assigneeId) {
            $this->notifyAssignee($task, $actor, __('Task assigned'), __('You were assigned the task :task.', [
                'task' => $task->title,
            ]));
            event(TaskAssigned::forModel($task, [
                'actor_id' => $actor->id,
                'assigned_to' => $assigneeId,
            ]));
        }

        return $task;
    }

    public function create(array $data, User $actor, ?Model $subject = null): Task
    {
        if ($subject) {
            return $this->createFor($subject, $data, $actor);
        }

        $this->validateLegacyData($data);

        $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
        $organization = Organization::query()->findOrFail($organizationId);
        $this->ensureDefaults($organization);

        $status = $data['status'] ?? 'pending';
        $priority = $data['priority'] ?? 'medium';

        $payload = [
            ...$data,
            'organization_id' => $organizationId,
            'status' => $status,
            'priority' => $priority,
            'status_id' => $data['status_id'] ?? $this->resolveStatusIdFromLegacy($organization, $status),
            'priority_id' => $data['priority_id'] ?? $this->resolvePriorityIdFromLegacy($organization, $priority),
            'created_by' => $actor->id,
            'completed_at' => $status === 'completed' ? now() : null,
        ];

        $this->syncLegacyFromCatalogIds($payload, $organization);

        $task = Task::query()->create($payload);
        $task = $task->fresh(['assignee', 'taskStatus', 'taskPriority']);

        event(TaskCreated::forModel($task, ['actor_id' => $actor->id]));

        return $task;
    }

    /**
     * Work-management create — project_id is required.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForProject(Project $project, array $data, User $actor, array $metadataValues = []): Task
    {
        return $this->createWorkManagement([
            ...$data,
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
        ], $actor, $metadataValues);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createWorkManagement(array $data, User $actor, array $metadataValues = []): Task
    {
        return DB::transaction(function () use ($data, $actor, $metadataValues) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $organization = Organization::query()->findOrFail($organizationId);

            if (empty($data['project_id'])) {
                throw ValidationException::withMessages([
                    'project_id' => __('A project is required for work-management tasks.'),
                ]);
            }

            $project = Project::query()
                ->where('organization_id', $organizationId)
                ->whereKey((int) $data['project_id'])
                ->firstOrFail();

            if ($project->isArchived()) {
                throw ValidationException::withMessages([
                    'project_id' => __('Cannot create tasks on an archived project.'),
                ]);
            }

            $this->ensureDefaults($organization);
            $this->validateTitle($data);

            if (isset($data['assigned_to'])) {
                $this->assertOrgMember($organization, (int) $data['assigned_to'], 'assigned_to');
            }

            $statusId = $data['status_id'] ?? TaskStatus::query()
                ->where('organization_id', $organizationId)
                ->where('is_default', true)
                ->value('id');

            $priorityId = $data['priority_id'] ?? TaskPriority::query()
                ->where('organization_id', $organizationId)
                ->where('is_default', true)
                ->value('id');

            $payload = [
                ...$data,
                'organization_id' => $organizationId,
                'project_id' => $project->id,
                'status_id' => $statusId,
                'priority_id' => $priorityId,
                'task_number' => $data['task_number'] ?? $this->nextTaskNumber($organization),
                'slug' => $data['slug'] ?? $this->generateSlug((string) $data['title'], $organizationId),
                'status' => $data['status'] ?? 'pending',
                'priority' => $data['priority'] ?? 'medium',
                'assigned_by' => isset($data['assigned_to']) ? $actor->id : ($data['assigned_by'] ?? null),
                'created_by' => $actor->id,
                'is_archived' => false,
                'completion_percentage' => $data['completion_percentage'] ?? 0,
            ];

            if (! empty($data['status']) && empty($data['status_id'])) {
                $payload['status_id'] = $this->resolveStatusIdFromLegacy($organization, (string) $data['status']);
            }

            if (! empty($data['priority']) && empty($data['priority_id'])) {
                $payload['priority_id'] = $this->resolvePriorityIdFromLegacy($organization, (string) $data['priority']);
            }

            $this->syncLegacyFromCatalogIds($payload, $organization);

            if (($payload['status'] ?? null) === 'completed' || $this->statusIsClosed((int) ($payload['status_id'] ?? 0))) {
                $payload['completed_at'] = $payload['completed_at'] ?? now();
            }

            $task = Task::query()->create($payload);
            $this->persistMetadata($task, $metadataValues);
            $task = $task->fresh(['assignee', 'taskStatus', 'taskPriority', 'project']);

            event(TaskCreated::forModel($task, ['actor_id' => $actor->id, 'project_id' => $project->id]));

            if ($task->assigned_to) {
                $this->notifyAssignee($task, $actor, __('Task assigned'), __('You were assigned the task :task.', [
                    'task' => $task->title,
                ]));
                event(TaskAssigned::forModel($task, [
                    'actor_id' => $actor->id,
                    'assigned_to' => $task->assigned_to,
                ]));
            }

            return $task;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data, User $actor, array $metadataValues = []): Task
    {
        $this->assertWritable($task);

        return DB::transaction(function () use ($task, $data, $actor, $metadataValues) {
            $previousAssignee = $task->assigned_to;
            $previousStatusId = $task->status_id;
            $organization = $task->organization;

            if (array_key_exists('assigned_to', $data) && $data['assigned_to']) {
                $this->assertOrgMember($organization, (int) $data['assigned_to'], 'assigned_to');
                $data['assigned_by'] = $actor->id;
            }

            if (array_key_exists('title', $data) || array_key_exists('slug', $data)) {
                $title = $data['title'] ?? $task->title;

                if (array_key_exists('title', $data)) {
                    $this->validateTitle($data);
                }

                if (array_key_exists('slug', $data)) {
                    $data['slug'] = Str::slug((string) $data['slug']) ?: $this->generateSlug((string) $title, (int) $task->organization_id, $task->id);
                } elseif (array_key_exists('title', $data) && $task->slug) {
                    $data['slug'] = $this->generateSlug((string) $title, (int) $task->organization_id, $task->id);
                }
            }

            if (array_key_exists('status', $data) && ! array_key_exists('status_id', $data)) {
                $data['status_id'] = $this->resolveStatusIdFromLegacy($organization, (string) $data['status']);
            }

            if (array_key_exists('priority', $data) && ! array_key_exists('priority_id', $data)) {
                $data['priority_id'] = $this->resolvePriorityIdFromLegacy($organization, (string) $data['priority']);
            }

            $this->syncLegacyFromCatalogIds($data, $organization, $task);

            if (array_key_exists('status_id', $data) || array_key_exists('status', $data)) {
                $closed = isset($data['status_id'])
                    ? $this->statusIsClosed((int) $data['status_id'])
                    : in_array($data['status'] ?? '', ['completed', 'cancelled'], true);

                $completing = ($data['status'] ?? null) === 'completed'
                    || (isset($data['status_id']) && $this->statusIsCompleted((int) $data['status_id']));

                if ($completing) {
                    app(TaskDependencyService::class)->assertCanComplete($task);
                }

                if ($closed && ! $task->completed_at) {
                    $data['completed_at'] = now();
                } elseif (! $closed && array_key_exists('status_id', $data)) {
                    $data['completed_at'] = null;
                }
            }

            $task->update($data);

            $changes = array_values(array_filter(
                array_keys($data),
                fn (string $attribute) => $task->wasChanged($attribute),
            ));

            $metadataResult = $this->persistMetadata($task, $metadataValues);
            $task = $task->fresh(['assignee', 'taskStatus', 'taskPriority', 'project']);

            if (($metadataResult['changed'] ?? false) === true) {
                $changes[] = 'metadata';
            }

            $runtime = app(WorkflowRuntimeContext::class);

            if ($changes !== []) {
                event(TaskUpdated::forModel(
                    $task,
                    ['actor_id' => $actor->id, 'changes' => $changes],
                    causationId: $runtime->causationId,
                    depth: $runtime->causationId ? $runtime->depth + 1 : 0,
                ));
            }

            if (array_key_exists('assigned_to', $data) && (int) $task->assigned_to !== (int) $previousAssignee) {
                if ($previousAssignee) {
                    event(TaskReassigned::forModel($task, [
                        'actor_id' => $actor->id,
                        'from' => $previousAssignee,
                        'to' => $task->assigned_to,
                    ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
                } else {
                    event(TaskAssigned::forModel($task, [
                        'actor_id' => $actor->id,
                        'assigned_to' => $task->assigned_to,
                    ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
                }

                $this->notifyAssignee($task, $actor, __('Task assigned'), __('You were assigned the task :task.', [
                    'task' => $task->title,
                ]));
            }

            if ((int) $task->status_id !== (int) $previousStatusId) {
                if ($task->taskStatus?->slug === 'in-progress') {
                    event(TaskStarted::forModel($task, ['actor_id' => $actor->id], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
                }

                if ($task->taskStatus?->slug === 'completed') {
                    event(TaskCompleted::forModel($task, ['actor_id' => $actor->id], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
                    app(WatcherService::class)->notifyWatchers(
                        $task,
                        'task.completed',
                        __(':actor completed task :task.', ['actor' => $actor->name, 'task' => $task->title]),
                        $actor,
                        __('Task completed'),
                    );
                } elseif ($previousStatusId && $task->taskStatus?->slug !== 'completed' && $task->wasChanged('completed_at') && $task->completed_at === null) {
                    app(WatcherService::class)->notifyWatchers(
                        $task,
                        'task.reopened',
                        __(':actor reopened task :task.', ['actor' => $actor->name, 'task' => $task->title]),
                        $actor,
                        __('Task reopened'),
                    );
                } else {
                    app(WatcherService::class)->notifyWatchers(
                        $task,
                        'task.status_changed',
                        __(':actor changed the status of :task.', ['actor' => $actor->name, 'task' => $task->title]),
                        $actor,
                        __('Task status updated'),
                    );
                }
            }

            if (in_array('due_date', $changes, true) || in_array('due_at', $changes, true)) {
                app(WatcherService::class)->notifyWatchers(
                    $task,
                    'task.due_date_changed',
                    __(':actor updated the due date for :task.', ['actor' => $actor->name, 'task' => $task->title]),
                    $actor,
                    __('Task due date updated'),
                );
            }

            if (in_array('completion_percentage', $changes, true) && (int) $task->status_id === (int) $previousStatusId) {
                app(WatcherService::class)->notifyWatchers(
                    $task,
                    'task.progress_updated',
                    __(':actor updated progress on :task.', ['actor' => $actor->name, 'task' => $task->title]),
                    $actor,
                    __('Task progress updated'),
                );
            }

            return $task;
        });
    }

    public function assign(Task $task, ?User $assignee, User $actor): Task
    {
        $this->assertWritable($task);

        if ($assignee) {
            $this->assertOrgMember($task->organization, $assignee->id, 'assigned_to');
        }

        return $this->update($task, [
            'assigned_to' => $assignee?->id,
            'assigned_by' => $assignee ? $actor->id : null,
        ], $actor);
    }

    public function complete(Task $task, User $actor): Task
    {
        $this->assertWritable($task);
        $this->ensureDefaults($task->organization);
        app(TaskDependencyService::class)->assertCanComplete($task);

        $completedStatus = TaskStatus::query()
            ->where('organization_id', $task->organization_id)
            ->where('slug', 'completed')
            ->first();

        $payload = [
            'status' => 'completed',
            'completed_at' => now(),
            'completion_percentage' => 100,
        ];

        if ($completedStatus) {
            $payload['status_id'] = $completedStatus->id;
        }

        return $this->update($task, $payload, $actor);
    }

    public function archive(Task $task, User $actor): Task
    {
        if ($task->isArchived()) {
            return $task;
        }

        $task->update(['is_archived' => true]);
        $task = $task->fresh(['assignee', 'taskStatus']);

        $runtime = app(WorkflowRuntimeContext::class);
        event(TaskArchived::forModel(
            $task,
            ['actor_id' => $actor->id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $task;
    }

    public function restore(Task $task, User $actor): Task
    {
        if (! $task->isArchived()) {
            return $task;
        }

        $task->update(['is_archived' => false]);
        $task = $task->fresh(['assignee', 'taskStatus']);

        $runtime = app(WorkflowRuntimeContext::class);
        event(TaskRestored::forModel(
            $task,
            ['actor_id' => $actor->id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $task;
    }

    public function calculateProgress(Task $task): int
    {
        $task->loadMissing(['checklists', 'children']);

        $checklistTotal = $task->checklists->count();
        $childTotal = $task->children->count();

        if ($checklistTotal === 0 && $childTotal === 0) {
            return (int) $task->completion_percentage;
        }

        $parts = [];

        if ($checklistTotal > 0) {
            $completed = $task->checklists->where('is_completed', true)->count();
            $parts[] = ($completed / $checklistTotal) * 100;
        }

        if ($childTotal > 0) {
            $avg = $task->children->avg(fn (Task $child) => $child->isClosed() ? 100 : (int) $child->completion_percentage);
            $parts[] = (float) $avg;
        }

        $progress = (int) round(array_sum($parts) / count($parts));
        $progress = max(0, min(100, $progress));

        if ((int) $task->completion_percentage !== $progress) {
            $task->update(['completion_percentage' => $progress]);
        }

        if ($task->parent_task_id) {
            $parent = Task::query()->find($task->parent_task_id);
            if ($parent) {
                $this->calculateProgress($parent);
            }
        }

        return $progress;
    }

    public function nextTaskNumber(Organization|int $organization): string
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;
        $prefix = (string) config('tasks.number_prefix', 'TASK');
        $padding = (int) config('tasks.number_padding', 4);

        $latestNumber = Task::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereNotNull('task_number')
            ->orderByDesc('id')
            ->value('task_number');

        $next = 1;

        if (is_string($latestNumber) && preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $latestNumber, $matches)) {
            $next = ((int) $matches[1]) + 1;
        } else {
            $next = Task::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->whereNotNull('task_number')
                ->count() + 1;
        }

        return $prefix.'-'.str_pad((string) $next, $padding, '0', STR_PAD_LEFT);
    }

    public function generateSlug(string $title, int $orgId, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug !== '' ? $slug : 'task';
        $candidate = $original;
        $count = 1;

        while ($this->slugExists($orgId, $candidate, $ignoreId)) {
            $candidate = $original.'-'.$count;
            $count++;
        }

        return $candidate;
    }

    protected function slugExists(int $orgId, string $slug, ?int $ignoreId): bool
    {
        $query = Task::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->where('slug', $slug);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    protected function ensureDefaults(Organization $organization): void
    {
        if (! TaskStatus::query()->where('organization_id', $organization->id)->exists()) {
            $this->defaults()->seedAll($organization);
        }
    }

    protected function resolveStatusIdFromLegacy(Organization $organization, string $legacyStatus): ?int
    {
        $map = config('tasks.legacy_status_slug_map', []);
        $slug = $map[$legacyStatus] ?? $legacyStatus;

        return TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->value('id');
    }

    protected function resolvePriorityIdFromLegacy(Organization $organization, string $legacyPriority): ?int
    {
        return TaskPriority::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $legacyPriority)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function syncLegacyFromCatalogIds(array &$payload, Organization $organization, ?Task $existing = null): void
    {
        if (array_key_exists('status_id', $payload) && $payload['status_id']) {
            $status = TaskStatus::query()
                ->where('organization_id', $organization->id)
                ->whereKey((int) $payload['status_id'])
                ->first();

            if ($status) {
                $legacyMap = config('tasks.status_slug_legacy_map', []);
                $payload['status'] = $legacyMap[$status->slug] ?? $existing?->status ?? 'pending';
            }
        }

        if (array_key_exists('priority_id', $payload) && $payload['priority_id']) {
            $priority = TaskPriority::query()
                ->where('organization_id', $organization->id)
                ->whereKey((int) $payload['priority_id'])
                ->first();

            if ($priority) {
                $legacyMap = config('tasks.priority_slug_legacy_map', []);
                $payload['priority'] = $legacyMap[$priority->slug] ?? $existing?->priority ?? 'medium';
            }
        }
    }

    protected function statusIsClosed(int $statusId): bool
    {
        if ($statusId <= 0) {
            return false;
        }

        return (bool) TaskStatus::query()->whereKey($statusId)->value('is_closed');
    }

    protected function statusIsCompleted(int $statusId): bool
    {
        if ($statusId <= 0) {
            return false;
        }

        return TaskStatus::query()->whereKey($statusId)->value('slug') === 'completed';
    }

    /**
     * @return array{changed: bool}|null
     */
    protected function persistMetadata(Task $task, array $metadataValues): ?array
    {
        if ($metadataValues === [] || ! $this->metadataForms()) {
            return null;
        }

        return $this->metadataForms()->persistValidatedValues($task, $metadataValues);
    }

    protected function assertWritable(Task $task): void
    {
        if ($task->isArchived()) {
            throw ValidationException::withMessages([
                'task' => __('Archived tasks are read-only.'),
            ]);
        }
    }

    protected function assertTenant(Model $subject, User $actor): void
    {
        if (! $subject->organization_id || ! $subject->organization->users()->whereKey($actor->id)->exists()) {
            throw ValidationException::withMessages(['actor' => 'The actor does not belong to the subject organization.']);
        }
    }

    protected function assertOrgMember(Organization $organization, int $userId, string $field): void
    {
        if (! $organization->users()->whereKey($userId)->exists()) {
            throw ValidationException::withMessages([
                $field => __('The selected user is not an organization member.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateTitle(array $data): void
    {
        if (trim((string) ($data['title'] ?? '')) === '') {
            throw ValidationException::withMessages(['title' => 'A task title is required.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateLegacyData(array $data): void
    {
        $this->validateTitle($data);

        if (! array_key_exists($data['status'] ?? 'pending', config('tasks.statuses', []))) {
            throw ValidationException::withMessages(['status' => 'Invalid task status.']);
        }

        if (! array_key_exists($data['priority'] ?? 'medium', config('tasks.priorities', []))) {
            throw ValidationException::withMessages(['priority' => 'Invalid task priority.']);
        }
    }

    protected function notifyAssignee(Task $task, User $actor, string $title, string $message): void
    {
        $recipient = $task->assignee;

        if (! $recipient || $recipient->id === $actor->id) {
            return;
        }

        $recipient->notify(new CrmNotification(
            title: $title,
            message: $message,
            actionUrl: Route::has('tasks.show') ? route('tasks.show', $task) : null,
            organizationId: (int) $task->organization_id,
        ));
    }

    protected function defaults(): TaskDefaultsService
    {
        return $this->defaults ??= app(TaskDefaultsService::class);
    }

    protected function metadataForms(): ?MetadataEntityFormService
    {
        if ($this->metadataForms !== null) {
            return $this->metadataForms;
        }

        if (! class_exists(MetadataEntityFormService::class)) {
            return null;
        }

        return $this->metadataForms = app(MetadataEntityFormService::class);
    }
}
