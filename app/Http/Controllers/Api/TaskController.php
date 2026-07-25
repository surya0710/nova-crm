<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\IndexApiTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function __construct(protected TaskService $taskService) {}

    public function index(IndexApiTaskRequest $request): AnonymousResourceCollection
    {
        $query = Task::query()
            ->with(['assignee', 'creator', 'taskStatus', 'taskPriority', 'project']);

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

        return TaskResource::collection(
            $query->latest()->paginate($request->perPage())
        );
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        $task->load([
            'assignee', 'creator', 'taskStatus', 'taskPriority', 'project',
            'checklists', 'comments.user', 'attachments.uploader', 'timeLogs.user',
        ]);

        return new TaskResource($task);
    }

    public function store(StoreTaskRequest $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return response()->json([
                'message' => __('Organization context is required.'),
            ], 422);
        }

        $validated = $request->validated();
        $taskable = $this->resolveTaskable($validated);

        if (! empty($validated['project_id'])) {
            $task = $this->taskService->createWorkManagement($validated, $request->user());
        } else {
            $task = $this->taskService->create($validated, $request->user(), $taskable);
        }

        $task->load(['assignee', 'taskStatus', 'taskPriority', 'project']);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $validated = $request->validated();
        $taskable = $this->resolveTaskable($validated);

        if ($request->has('taskable_type')) {
            $validated['taskable_type'] = $taskable?->getMorphClass();
            $validated['taskable_id'] = $taskable?->getKey();
        }

        $task = $this->taskService->update($task, $validated, $request->user());
        $task->load(['assignee', 'taskStatus', 'taskPriority', 'project']);

        return new TaskResource($task);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(['success' => true]);
    }

    public function archive(Request $request, Task $task): TaskResource
    {
        $this->authorize('archive', $task);

        $task = $this->taskService->archive($task, $request->user());
        $task->load(['taskStatus', 'assignee']);

        return new TaskResource($task);
    }

    public function restore(Request $request, Task $task): TaskResource
    {
        $this->authorize('restore', $task);

        $task = $this->taskService->restore($task, $request->user());
        $task->load(['taskStatus', 'assignee']);

        return new TaskResource($task);
    }

    public function assign(AssignTaskRequest $request, Task $task): TaskResource|JsonResponse
    {
        try {
            $assigneeId = $request->validated('assigned_to');
            $assignee = $assigneeId ? User::query()->find($assigneeId) : null;
            $task = $this->taskService->assign($task, $assignee, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $task->load(['assignee', 'taskStatus']);

        return new TaskResource($task);
    }

    public function complete(Request $request, Task $task): TaskResource|JsonResponse
    {
        $this->authorize('update', $task);

        try {
            $task = $this->taskService->complete($task, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $task->load(['assignee', 'taskStatus']);

        return new TaskResource($task);
    }

    protected function resolveTaskable(array $validated): ?Model
    {
        if (empty($validated['taskable_type']) || empty($validated['taskable_id'])) {
            return null;
        }

        $class = config('tasks.taskable.'.$validated['taskable_type']);

        if (! $class) {
            return null;
        }

        return $class::query()->find($validated['taskable_id']);
    }
}
