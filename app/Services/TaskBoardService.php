<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Notifications\CrmNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaskBoardService
{
    public function __construct(
        protected TaskService $tasks,
        protected TaskDefaultsService $defaults,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(Organization $organization, User $user, array $filters = []): array
    {
        $this->defaults->seedAll($organization);

        $preferences = $this->preferences($organization, $user);
        $filters = array_merge($preferences['filters'] ?? [], $filters);
        $swimlane = $filters['swimlane'] ?? $preferences['swimlane'] ?? 'none';

        $statuses = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $columnsConfig = $this->resolveColumns($statuses);
        $query = $this->filteredTasksQuery($organization, $filters);
        $tasks = $query->get();

        $columns = [];
        foreach ($columnsConfig as $key => $column) {
            $statusIds = $column['status_ids'];
            $columnTasks = $tasks->filter(fn (Task $task) => in_array((int) $task->status_id, $statusIds, true))->values();

            $columns[$key] = [
                'key' => $key,
                'label' => $column['label'],
                'status_ids' => $statusIds,
                'primary_status_id' => $column['primary_status_id'],
                'color' => $column['color'],
                'wip_limit' => $column['wip_limit'],
                'wip_exceeded' => $column['wip_limit'] !== null && $columnTasks->count() > $column['wip_limit'],
                'count' => $columnTasks->count(),
                'tasks' => $columnTasks->map(fn (Task $task) => $this->cardPayload($task))->all(),
            ];
        }

        return [
            'columns' => $columns,
            'metrics' => $this->metrics($tasks, $columns),
            'swimlane' => $swimlane,
            'swimlanes' => $this->groupBySwimlane($tasks, $swimlane),
            'filters' => $filters,
            'preferences' => $preferences,
            'column_keys' => array_keys($columnsConfig),
        ];
    }

    /**
     * Move / reorder a task on the board. Always updates via TaskService.
     *
     * @param  array{status_id?: int|null, column?: string|null, sort_order?: int|null, before_task_id?: int|null, after_task_id?: int|null}  $payload
     */
    public function move(Task $task, array $payload, User $actor): array
    {
        $organization = $task->organization;
        $this->defaults->seedAll($organization);

        $statusId = $payload['status_id'] ?? null;
        if (! $statusId && ! empty($payload['column'])) {
            $statusId = $this->primaryStatusIdForColumn($organization, (string) $payload['column']);
        }

        $updates = [];
        if ($statusId) {
            $updates['status_id'] = (int) $statusId;
        }

        $sortOrder = $payload['sort_order'] ?? $this->resolveSortOrder($task, $payload);
        if ($sortOrder !== null) {
            $updates['sort_order'] = (int) $sortOrder;
        }

        if ($updates !== []) {
            $this->tasks->update($task, $updates, $actor);
        }

        $task = $task->fresh([
            'assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint', 'labels',
            'checklists' => fn ($q) => $q->select('id', 'task_id', 'is_completed'),
        ]);
        $task->loadCount(['comments', 'attachments', 'predecessorDependencies']);

        $this->checkWipLimit($task, $actor);
        $this->bustMetricsCache((int) $task->organization_id);

        $tasks = $this->filteredTasksQuery($task->organization, [])->get();
        $statuses = TaskStatus::query()
            ->where('organization_id', $task->organization_id)
            ->orderBy('sort_order')
            ->get();
        $columnsConfig = $this->resolveColumns($statuses);
        $columnsForMetrics = [];
        foreach ($columnsConfig as $key => $column) {
            $columnsForMetrics[$key] = [
                'count' => $tasks->filter(fn (Task $t) => in_array((int) $t->status_id, $column['status_ids'], true))->count(),
            ];
        }

        return [
            'task' => $this->cardPayload($task),
            'metrics' => $this->metrics($tasks, $columnsForMetrics),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float>
     */
    public function metrics(Collection $tasks, array $columns = []): array
    {
        $byColumn = [];
        foreach ($columns as $key => $column) {
            $byColumn[$key] = (int) ($column['count'] ?? 0);
        }

        $estimated = round((float) $tasks->sum(fn (Task $t) => (float) ($t->estimated_hours ?? 0)), 2);
        $logged = round((float) $tasks->sum(fn (Task $t) => (float) ($t->actual_hours ?? 0)), 2);
        $avg = $tasks->isEmpty()
            ? 0
            : (int) round((float) $tasks->avg(fn (Task $t) => (int) ($t->completion_percentage ?? 0)));

        $overdue = $tasks->filter(fn (Task $t) => $t->isOverdue())->count();

        return [
            'total' => $tasks->count(),
            'todo' => $byColumn['todo'] ?? $tasks->filter(fn (Task $t) => ($t->taskStatus?->slug ?? '') === 'to-do')->count(),
            'in_progress' => $byColumn['in_progress'] ?? $tasks->filter(fn (Task $t) => ($t->taskStatus?->slug ?? '') === 'in-progress')->count(),
            'review' => $byColumn['review'] ?? $tasks->filter(fn (Task $t) => ($t->taskStatus?->slug ?? '') === 'review')->count(),
            'done' => $byColumn['done'] ?? $tasks->filter(fn (Task $t) => (bool) $t->taskStatus?->is_closed)->count(),
            'overdue' => $overdue,
            'average_completion' => $avg,
            'estimated_hours' => $estimated,
            'logged_hours' => $logged,
            'columns' => $byColumn,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float>
     */
    public function metricsForFilters(Organization $organization, array $filters = []): array
    {
        $statuses = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->orderBy('sort_order')
            ->get();
        $columnsConfig = $this->resolveColumns($statuses);
        $tasks = $this->filteredTasksQuery($organization, $filters)->get();
        $columns = [];
        foreach ($columnsConfig as $key => $column) {
            $columns[$key] = [
                'count' => $tasks->filter(fn (Task $t) => in_array((int) $t->status_id, $column['status_ids'], true))->count(),
            ];
        }

        return $this->metrics($tasks, $columns);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float>
     */
    public function cachedMetrics(Organization $organization, array $filters): array
    {
        $key = 'task-board-metrics:'.$organization->id.':'.md5(json_encode($filters));

        return Cache::remember($key, now()->addSeconds(30), function () use ($organization, $filters) {
            return $this->metricsForFilters($organization, $filters);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function savePreferences(Organization $organization, User $user, array $attributes): array
    {
        $prefs = UserUiPreference::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            ['theme' => 'light', 'density' => 'comfortable']
        );

        $meta = $prefs->meta ?? [];
        $board = $meta['task_board'] ?? [];

        if (array_key_exists('swimlane', $attributes)) {
            $swimlane = (string) $attributes['swimlane'];
            if (! array_key_exists($swimlane, config('tasks.board.swimlanes', ['none' => 'None']))) {
                throw ValidationException::withMessages(['swimlane' => __('Invalid swimlane.')]);
            }
            $board['swimlane'] = $swimlane;
        }

        if (array_key_exists('filters', $attributes) && is_array($attributes['filters'])) {
            $board['filters'] = $attributes['filters'];
        }

        if (! empty($attributes['save_view']) && is_array($attributes['save_view'])) {
            $views = $board['saved_views'] ?? [];
            $view = [
                'id' => (string) ($attributes['save_view']['id'] ?? (string) Str::uuid()),
                'name' => trim((string) ($attributes['save_view']['name'] ?? 'Saved view')),
                'filters' => $attributes['save_view']['filters'] ?? ($board['filters'] ?? []),
                'swimlane' => $attributes['save_view']['swimlane'] ?? ($board['swimlane'] ?? 'none'),
            ];
            $views = collect($views)->reject(fn ($v) => ($v['id'] ?? null) === $view['id'])->values()->all();
            $views[] = $view;
            $board['saved_views'] = $views;
            $board['active_view_id'] = $view['id'];
        }

        if (! empty($attributes['active_view_id'])) {
            $board['active_view_id'] = (string) $attributes['active_view_id'];
            $active = collect($board['saved_views'] ?? [])->firstWhere('id', $board['active_view_id']);
            if ($active) {
                $board['filters'] = $active['filters'] ?? [];
                $board['swimlane'] = $active['swimlane'] ?? 'none';
            }
        }

        if (! empty($attributes['delete_view_id'])) {
            $board['saved_views'] = collect($board['saved_views'] ?? [])
                ->reject(fn ($v) => ($v['id'] ?? null) === $attributes['delete_view_id'])
                ->values()
                ->all();
            if (($board['active_view_id'] ?? null) === $attributes['delete_view_id']) {
                unset($board['active_view_id']);
            }
        }

        $meta['task_board'] = $board;
        $prefs->update(['meta' => $meta]);

        return $board;
    }

    /**
     * @return array<string, mixed>
     */
    public function preferences(Organization $organization, User $user): array
    {
        $prefs = UserUiPreference::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        $board = $prefs?->meta['task_board'] ?? [];

        return [
            'swimlane' => $board['swimlane'] ?? 'none',
            'filters' => $board['filters'] ?? [],
            'saved_views' => $board['saved_views'] ?? [],
            'active_view_id' => $board['active_view_id'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cardPayload(Task $task): array
    {
        $task->loadMissing([
            'assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint', 'labels',
        ]);

        if (! $task->relationLoaded('checklists')) {
            $task->load(['checklists' => fn ($q) => $q->select('id', 'task_id', 'is_completed')]);
        }

        $checklistTotal = $task->checklists->count();
        $checklistDone = $task->checklists->where('is_completed', true)->count();

        $commentCount = $task->comments_count
            ?? ($task->relationLoaded('comments') ? $task->comments->count() : $task->comments()->count());
        $attachmentCount = $task->attachments_count
            ?? ($task->relationLoaded('attachments') ? $task->attachments->count() : $task->attachments()->count());
        $dependencyCount = $task->predecessor_dependencies_count
            ?? $task->predecessorDependencies()->count();

        return [
            'id' => $task->id,
            'title' => $task->title,
            'task_number' => $task->task_number,
            'status_id' => $task->status_id,
            'status' => $task->taskStatus?->name ?? $task->status,
            'status_slug' => $task->taskStatus?->slug,
            'priority' => $task->taskPriority?->name ?? $task->priority,
            'priority_slug' => $task->taskPriority?->slug ?? $task->priority,
            'priority_color' => $task->taskPriority?->color,
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
                'initials' => Str::upper(Str::substr($task->assignee->name, 0, 1)),
            ] : null,
            'due_date' => $task->due_date?->toDateString() ?? $task->due_at?->toDateString(),
            'is_overdue' => $task->isOverdue(),
            'completion_percentage' => (int) ($task->completion_percentage ?? 0),
            'checklist' => [
                'done' => $checklistDone,
                'total' => $checklistTotal,
            ],
            'estimated_hours' => (float) ($task->estimated_hours ?? 0),
            'logged_hours' => (float) ($task->actual_hours ?? 0),
            'attachment_count' => (int) $attachmentCount,
            'comment_count' => (int) $commentCount,
            'has_dependencies' => $dependencyCount > 0,
            'dependency_count' => (int) $dependencyCount,
            'project_id' => $task->project_id,
            'project_name' => $task->project?->name,
            'milestone_id' => $task->milestone_id,
            'milestone_name' => $task->milestone?->name,
            'sprint_id' => $task->sprint_id,
            'sprint_name' => $task->sprint?->name,
            'labels' => $task->labels->map(fn ($label) => [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ])->values()->all(),
            'sort_order' => (int) $task->sort_order,
            'url' => route('tasks.show', $task),
            'can_update' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredTasksQuery(Organization $organization, array $filters): Builder
    {
        $query = Task::query()
            ->where('organization_id', $organization->id)
            ->where('is_archived', false)
            ->with([
                'assignee:id,name',
                'taskStatus',
                'taskPriority',
                'project:id,name',
                'milestone:id,name',
                'sprint:id,name',
                'labels:id,name,color',
                'checklists:id,task_id,is_completed',
            ])
            ->withCount(['comments', 'attachments', 'predecessorDependencies'])
            ->orderBy('sort_order')
            ->orderBy('id');

        if (! empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }
        if (! empty($filters['sprint_id'])) {
            $query->where('sprint_id', (int) $filters['sprint_id']);
        }
        if (! empty($filters['milestone_id'])) {
            $query->where('milestone_id', (int) $filters['milestone_id']);
        }
        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }
        if (! empty($filters['status_id'])) {
            $query->where('status_id', (int) $filters['status_id']);
        }
        if (! empty($filters['priority_id'])) {
            $query->where('priority_id', (int) $filters['priority_id']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (! empty($filters['label_id'])) {
            $query->whereHas('labels', fn ($q) => $q->where('project_labels.id', (int) $filters['label_id']));
        }
        if (! empty($filters['due_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_from']);
        }
        if (! empty($filters['due_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_to']);
        }
        if (! empty($filters['overdue_only'])) {
            $query->where(function (Builder $q): void {
                $q->where(function (Builder $inner): void {
                    $inner->whereNotNull('due_date')->whereDate('due_date', '<', today());
                })->orWhere(function (Builder $inner): void {
                    $inner->whereNull('due_date')->whereNotNull('due_at')->where('due_at', '<', now());
                });
            })->whereHas('taskStatus', fn ($s) => $s->where('is_closed', false));
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('task_number', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * @param  Collection<int, TaskStatus>  $statuses
     * @return array<string, array<string, mixed>>
     */
    protected function resolveColumns(Collection $statuses): array
    {
        $config = config('tasks.board.columns', []);
        $bySlug = $statuses->keyBy('slug');
        $columns = [];

        foreach ($config as $key => $definition) {
            $slugs = $definition['slugs'] ?? [$key];
            $matched = collect($slugs)
                ->map(fn ($slug) => $bySlug->get($slug))
                ->filter()
                ->values();

            if ($matched->isEmpty()) {
                continue;
            }

            $primary = $matched->first();
            $wip = $matched->pluck('wip_limit')->filter(fn ($v) => $v !== null)->min();

            $columns[$key] = [
                'label' => $definition['label'] ?? $primary->name,
                'status_ids' => $matched->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'primary_status_id' => (int) $primary->id,
                'color' => $primary->color,
                'wip_limit' => $wip !== null ? (int) $wip : ($definition['wip_limit'] ?? null),
            ];
        }

        // Fallback: show all statuses as columns when config matches nothing.
        if ($columns === []) {
            foreach ($statuses as $status) {
                $columns[$status->slug] = [
                    'label' => $status->name,
                    'status_ids' => [(int) $status->id],
                    'primary_status_id' => (int) $status->id,
                    'color' => $status->color,
                    'wip_limit' => $status->wip_limit,
                ];
            }
        }

        return $columns;
    }

    protected function primaryStatusIdForColumn(Organization $organization, string $column): ?int
    {
        $statuses = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->orderBy('sort_order')
            ->get();

        $resolved = $this->resolveColumns($statuses);

        return $resolved[$column]['primary_status_id'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveSortOrder(Task $task, array $payload): ?int
    {
        if (array_key_exists('sort_order', $payload) && $payload['sort_order'] !== null) {
            return (int) $payload['sort_order'];
        }

        $beforeId = $payload['before_task_id'] ?? null;
        $afterId = $payload['after_task_id'] ?? null;

        if ($beforeId) {
            $before = Task::query()->find($beforeId);

            return $before ? max(0, (int) $before->sort_order - 1) : null;
        }

        if ($afterId) {
            $after = Task::query()->find($afterId);

            return $after ? ((int) $after->sort_order + 1) : null;
        }

        return null;
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return list<array<string, mixed>>
     */
    protected function groupBySwimlane(Collection $tasks, string $swimlane): array
    {
        if ($swimlane === 'none' || ! array_key_exists($swimlane, config('tasks.board.swimlanes', []))) {
            return [];
        }

        $groups = match ($swimlane) {
            'assignee' => $tasks->groupBy(fn (Task $t) => $t->assigned_to ?: 0),
            'priority' => $tasks->groupBy(fn (Task $t) => $t->priority_id ?: ($t->priority ?? 'none')),
            'milestone' => $tasks->groupBy(fn (Task $t) => $t->milestone_id ?: 0),
            'sprint' => $tasks->groupBy(fn (Task $t) => $t->sprint_id ?: 0),
            'status' => $tasks->groupBy(fn (Task $t) => $t->status_id ?: 0),
            default => collect(),
        };

        return $groups->map(function (Collection $group, $key) use ($swimlane) {
            $first = $group->first();
            $label = match ($swimlane) {
                'assignee' => $first?->assignee?->name ?? __('Unassigned'),
                'priority' => $first?->taskPriority?->name ?? ($first?->priority ?? __('No priority')),
                'milestone' => $first?->milestone?->name ?? __('No milestone'),
                'sprint' => $first?->sprint?->name ?? __('No sprint'),
                'status' => $first?->taskStatus?->name ?? __('No status'),
                default => (string) $key,
            };

            return [
                'key' => (string) $key,
                'label' => $label,
                'task_ids' => $group->pluck('id')->all(),
                'count' => $group->count(),
            ];
        })->values()->all();
    }

    protected function checkWipLimit(Task $task, User $actor): void
    {
        if (! config('tasks.board.wip_notify', true) || ! $task->status_id) {
            return;
        }

        $status = $task->taskStatus ?? TaskStatus::query()->find($task->status_id);
        if (! $status || $status->wip_limit === null) {
            return;
        }

        $count = Task::query()
            ->where('organization_id', $task->organization_id)
            ->where('status_id', $status->id)
            ->where('is_archived', false)
            ->count();

        if ($count <= (int) $status->wip_limit) {
            return;
        }

        $task->loadMissing('project.manager', 'project.owner');
        $recipients = collect([$task->project?->manager, $task->project?->owner])
            ->filter()
            ->unique('id');

        foreach ($recipients as $recipient) {
            if ($recipient->id === $actor->id) {
                continue;
            }

            $recipient->notify(new CrmNotification(
                title: __('WIP limit exceeded'),
                message: __(':status has :count tasks (limit :limit).', [
                    'status' => $status->name,
                    'count' => $count,
                    'limit' => $status->wip_limit,
                ]),
                actionUrl: Route::has('tasks.board') ? route('tasks.board') : null,
                organizationId: (int) $task->organization_id,
            ));
        }
    }

    protected function bustMetricsCache(int $organizationId): void
    {
        Cache::forget('task-board-metrics:'.$organizationId.':'.md5(json_encode([])));
    }
}
