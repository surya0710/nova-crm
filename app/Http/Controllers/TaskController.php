<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\MetadataEntityFormService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService,
        protected MetadataEntityFormService $metadataForms,
    ) {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();
        $query = $this->filteredQuery($request);

        return view('tasks.index', [
            'tasks' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'assignees' => $this->organizationMembers($organization),
            'statuses' => TaskStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
            'priorities' => TaskPriority::query()->orderBy('level')->orderBy('name')->get(),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only([
                'search', 'status', 'status_id', 'priority', 'priority_id',
                'assigned_to', 'project_id', 'filter', 'is_archived',
            ]),
        ]);
    }

    public function list(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', Task::class);

        $organization = $tenant->get();
        $query = $this->filteredQuery($request);

        return view('tasks.list', [
            'tasks' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'assignees' => $this->organizationMembers($organization),
            'statuses' => TaskStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
            'priorities' => TaskPriority::query()->orderBy('level')->orderBy('name')->get(),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only([
                'search', 'status', 'status_id', 'priority', 'priority_id',
                'assigned_to', 'project_id', 'filter', 'is_archived',
            ]),
            'viewMode' => 'list',
        ]);
    }

    public function board(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', Task::class);

        $organization = $tenant->get();
        $query = $this->filteredQuery($request)->where('is_archived', false);
        $tasks = $query->with(['assignee', 'taskStatus', 'taskPriority', 'project'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $statuses = TaskStatus::query()->orderBy('sort_order')->orderBy('name')->get();

        $columns = $statuses->mapWithKeys(function (TaskStatus $status) use ($tasks) {
            return [
                $status->id => $tasks->where('status_id', $status->id)->values(),
            ];
        });

        $legacy = $tasks->whereNull('status_id')->groupBy('status');

        return view('tasks.board', [
            'organization' => $organization,
            'statuses' => $statuses,
            'columns' => $columns,
            'legacy' => $legacy,
            'filters' => $request->only(['search', 'assigned_to', 'project_id', 'priority_id']),
            'assignees' => $this->organizationMembers($organization),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function timeline(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', Task::class);

        $organization = $tenant->get();
        $query = $this->filteredQuery($request)
            ->where('is_archived', false)
            ->where(function (Builder $q) {
                $q->whereNotNull('start_date')
                    ->orWhereNotNull('due_date')
                    ->orWhereNotNull('due_at');
            })
            ->orderByRaw('COALESCE(start_date, due_date, DATE(due_at)) ASC');

        return view('tasks.timeline', [
            'tasks' => $query->with(['assignee', 'taskStatus', 'project'])->paginate(50)->withQueryString(),
            'organization' => $organization,
            'filters' => $request->only(['search', 'assigned_to', 'project_id', 'status_id']),
        ]);
    }

    public function projectIndex(Request $request, Project $project): View
    {
        $this->authorize('viewAny', Task::class);

        $request->merge(['project_id' => $project->id]);
        $query = $this->filteredQuery($request);

        return view('tasks.index', [
            'tasks' => $query->paginate(15)->withQueryString(),
            'organization' => $project->organization,
            'project' => $project,
            'assignees' => $this->organizationMembers($project->organization),
            'statuses' => TaskStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
            'priorities' => TaskPriority::query()->orderBy('level')->orderBy('name')->get(),
            'projects' => collect([$project]),
            'filters' => $request->only([
                'search', 'status', 'status_id', 'priority', 'priority_id',
                'assigned_to', 'project_id', 'filter', 'is_archived',
            ]),
        ]);
    }

    public function create(Request $request, TenantContext $tenant): View
    {
        $task = new Task([
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDay(),
            'project_id' => $request->integer('project_id') ?: null,
        ]);

        if ($request->filled('taskable_type') && $request->filled('taskable_id')) {
            $task->taskable_type = config('tasks.taskable.'.$request->string('taskable_type'));
            $task->taskable_id = $request->integer('taskable_id');
        }

        return view('tasks.create', [
            'task' => $task,
            'assignees' => $this->organizationMembers($tenant->get()),
            'taskableOptions' => $this->taskableOptions($tenant->get()),
            'statuses' => TaskStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
            'priorities' => TaskPriority::query()->orderBy('level')->orderBy('name')->get(),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'task', 'create'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function store(StoreTaskRequest $request, TenantContext $tenant): RedirectResponse
    {
        $validated = $request->validated();
        $taskable = $this->resolveTaskable($validated);
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(null, $tenant->get(), 'task', 'create', $request);

        if (! empty($validated['project_id'])) {
            $task = $this->taskService->createWorkManagement($validated, $request->user(), $metadataValues);
        } else {
            $task = $this->taskService->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'priority' => $validated['priority'] ?? 'medium',
                'status_id' => $validated['status_id'] ?? null,
                'priority_id' => $validated['priority_id'] ?? null,
                'due_at' => $validated['due_at'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'assigned_to' => $validated['assigned_to'] ?? null,
                'estimated_hours' => $validated['estimated_hours'] ?? null,
                'parent_task_id' => $validated['parent_task_id'] ?? null,
                'milestone_id' => $validated['milestone_id'] ?? null,
            ], $request->user(), $taskable);

            if ($metadataValues !== []) {
                $this->taskService->update($task, [], $request->user(), $metadataValues);
            }
        }

        if ($request->boolean('redirect_back') && $taskable) {
            return back()->with('status', 'task-created');
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-created');
    }

    public function show(Task $task): View
    {
        $task->load([
            'assignee', 'creator', 'taskable', 'project', 'milestone', 'taskStatus', 'taskPriority',
            'checklists', 'comments.user', 'attachments.uploader', 'timeLogs.user',
            'predecessorDependencies.predecessor', 'successorDependencies.successor',
        ]);

        return view('tasks.show', [
            'task' => $task,
            'metadataFields' => $this->metadataForms->fieldsFor($task->organization, 'task', 'detail'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function edit(Task $task, TenantContext $tenant): View
    {
        return view('tasks.edit', [
            'task' => $task,
            'assignees' => $this->organizationMembers($tenant->get()),
            'taskableOptions' => $this->taskableOptions($tenant->get()),
            'statuses' => TaskStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
            'priorities' => TaskPriority::query()->orderBy('level')->orderBy('name')->get(),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'task', 'edit'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task, TenantContext $tenant): RedirectResponse
    {
        $validated = $request->validated();
        $taskable = $this->resolveTaskable($validated);
        $metadataValues = $this->metadataForms->validatedValuesFromRequest($task, $tenant->get(), 'task', 'edit', $request);

        if ($request->has('taskable_type')) {
            $validated['taskable_type'] = $taskable?->getMorphClass();
            $validated['taskable_id'] = $taskable?->getKey();
        }

        $this->taskService->update($task, $validated, $request->user(), $metadataValues);

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-updated');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('status', 'task-deleted');
    }

    public function complete(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('updateOwnWork', $task);

        $this->taskService->complete($task, $request->user());

        return back()->with('status', 'task-completed');
    }

    public function archive(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('archive', $task);

        $this->taskService->archive($task, $request->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-archived');
    }

    public function restore(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('restore', $task);

        $this->taskService->restore($task, $request->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'task-restored');
    }

    public function assign(AssignTaskRequest $request, Task $task): RedirectResponse
    {
        $assigneeId = $request->validated('assigned_to');
        $assignee = $assigneeId ? User::query()->find($assigneeId) : null;

        $this->taskService->assign($task, $assignee, $request->user());

        return back()->with('status', 'task-assigned');
    }

    protected function filteredQuery(Request $request): Builder
    {
        $query = Task::query()
            ->with(['assignee', 'creator', 'taskable', 'taskStatus', 'taskPriority', 'project'])
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('task_number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($statusId = $request->integer('status_id')) {
            $query->where('status_id', $statusId);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        if ($priorityId = $request->integer('priority_id')) {
            $query->where('priority_id', $priorityId);
        }

        if ($assignedTo = $request->integer('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($projectId = $request->integer('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($request->has('is_archived')) {
            $query->where('is_archived', $request->boolean('is_archived'));
        } else {
            $query->where('is_archived', false);
        }

        if ($request->string('filter')->toString() === 'overdue') {
            $query->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('status_id')
                        ->whereHas('taskStatus', fn ($s) => $s->where('is_closed', false));
                })->orWhere(function (Builder $inner) {
                    $inner->whereNull('status_id')
                        ->whereIn('status', ['pending', 'in_progress']);
                });
            })->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('due_date')->whereDate('due_date', '<', today());
                })->orWhere(function (Builder $inner) {
                    $inner->whereNull('due_date')->whereNotNull('due_at')->where('due_at', '<', now());
                });
            });
        }

        if ($request->string('filter')->toString() === 'due_today') {
            $query->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('status_id')
                        ->whereHas('taskStatus', fn ($s) => $s->where('is_closed', false));
                })->orWhere(function (Builder $inner) {
                    $inner->whereNull('status_id')
                        ->whereIn('status', ['pending', 'in_progress']);
                });
            })->where(function (Builder $q) {
                $q->whereDate('due_date', today())
                    ->orWhere(function (Builder $inner) {
                        $inner->whereNull('due_date')->whereDate('due_at', today());
                    });
            });
        }

        return $query;
    }

    /**
     * @return Collection<int, User>
     */
    protected function organizationMembers($organization)
    {
        if (! $organization) {
            return collect();
        }

        return $organization->users()->orderBy('name')->get();
    }

    /**
     * @return array<string, array<int, array{id: int, label: string}>>
     */
    protected function taskableOptions($organization): array
    {
        return [
            'lead' => Lead::query()->orderBy('name')->get()->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'label' => $lead->name,
            ])->all(),
            'customer' => Customer::query()->orderBy('name')->get()->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'label' => $customer->display_name,
            ])->all(),
            'opportunity' => Opportunity::query()->orderBy('title')->get()->map(fn (Opportunity $opportunity) => [
                'id' => $opportunity->id,
                'label' => $opportunity->title,
            ])->all(),
        ];
    }

    protected function resolveTaskable(array $validated): ?Model
    {
        if (empty($validated['taskable_type']) || empty($validated['taskable_id'])) {
            return null;
        }

        $class = config('tasks.taskable.'.$validated['taskable_type']);

        return $class::query()->find($validated['taskable_id']);
    }
}
