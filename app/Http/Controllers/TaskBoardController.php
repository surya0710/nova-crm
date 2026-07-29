<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectLabel;
use App\Models\ProjectMilestone;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\BacklogService;
use App\Services\ChecklistService;
use App\Services\SprintService;
use App\Services\TaskBoardService;
use App\Services\TaskCommentService;
use App\Services\TaskService;
use App\Services\TenantContext;
use App\Services\TimeTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskBoardController extends Controller
{
    public function __construct(
        protected TaskBoardService $board,
        protected BacklogService $backlog,
        protected SprintService $sprints,
        protected TaskService $tasks,
        protected ChecklistService $checklists,
        protected TaskCommentService $comments,
        protected TimeTrackingService $timeTracking,
    ) {}

    public function board(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', Task::class);

        $organization = $tenant->get();
        $filters = $request->only([
            'project_id', 'sprint_id', 'milestone_id', 'assigned_to', 'status_id',
            'priority_id', 'priority', 'label_id', 'due_from', 'due_to', 'overdue_only',
            'search', 'swimlane',
        ]);

        if ($request->boolean('overdue_only')) {
            $filters['overdue_only'] = true;
        }

        $payload = $this->board->build($organization, $request->user(), $filters);

        return view('tasks.board', [
            'organization' => $organization,
            'board' => $payload,
            'metrics' => $payload['metrics'],
            'columns' => $payload['columns'],
            'swimlanes' => $payload['swimlanes'],
            'preferences' => $payload['preferences'],
            'filters' => $payload['filters'],
            'swimlaneOptions' => config('tasks.board.swimlanes', []),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->limit(200)->get(['id', 'name']),
            'sprints' => $this->sprints->forOrganization($organization, $request->integer('project_id') ?: null),
            'milestones' => ProjectMilestone::query()->orderBy('due_date')->limit(200)->get(['id', 'name', 'project_id']),
            'assignees' => $organization->users()->orderBy('name')->limit(200)->get(['users.id', 'users.name']),
            'statuses' => TaskStatus::query()->orderBy('sort_order')->get(),
            'priorities' => TaskPriority::query()->orderBy('level')->get(),
            'labels' => ProjectLabel::query()->orderBy('name')->limit(100)->get(['id', 'name', 'color']),
            'moveUrlTemplate' => url('/tasks/__TASK__/board/move'),
            'prefsUrl' => route('tasks.board.preferences'),
            'quickActionUrlTemplate' => url('/tasks/__TASK__/board/quick-action'),
        ]);
    }

    public function move(Request $request, Task $task): JsonResponse
    {
        $this->authorize('updateOwnWork', $task);

        $validated = $request->validate([
            'status_id' => ['nullable', 'integer', 'exists:task_statuses,id'],
            'column' => ['nullable', 'string', 'max:64'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'before_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'after_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);

        // Full update permission for status changes; assignees can still complete own work via TaskService rules.
        if (! empty($validated['status_id']) || ! empty($validated['column'])) {
            if (! $request->user()->can('update', $task) && ! $request->user()->can('updateOwnWork', $task)) {
                abort(403);
            }
        }

        $result = $this->board->move($task, $validated, $request->user());

        return response()->json(['data' => $result]);
    }

    public function preferences(Request $request, TenantContext $tenant): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $validated = $request->validate([
            'swimlane' => ['nullable', 'string', 'max:32'],
            'filters' => ['nullable', 'array'],
            'save_view' => ['nullable', 'array'],
            'save_view.name' => ['required_with:save_view', 'string', 'max:120'],
            'save_view.id' => ['nullable', 'string', 'max:64'],
            'save_view.filters' => ['nullable', 'array'],
            'save_view.swimlane' => ['nullable', 'string', 'max:32'],
            'active_view_id' => ['nullable', 'string', 'max:64'],
            'delete_view_id' => ['nullable', 'string', 'max:64'],
        ]);

        $board = $this->board->savePreferences($tenant->get(), $request->user(), $validated);

        return response()->json(['data' => $board]);
    }

    public function quickAction(Request $request, Task $task): JsonResponse
    {
        $action = $request->string('action')->toString();

        return match ($action) {
            'status' => $this->quickStatus($request, $task),
            'assign' => $this->quickAssign($request, $task),
            'priority' => $this->quickPriority($request, $task),
            'log_time' => $this->quickLogTime($request, $task),
            'checklist' => $this->quickChecklist($request, $task),
            'comment' => $this->quickComment($request, $task),
            default => response()->json(['message' => __('Unknown action.')], 422),
        };
    }

    protected function quickStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorize('updateOwnWork', $task);
        $validated = $request->validate([
            'status_id' => ['required', 'integer', 'exists:task_statuses,id'],
        ]);

        $updated = $this->tasks->update($task, ['status_id' => $validated['status_id']], $request->user());

        return response()->json(['data' => $this->board->cardPayload($updated->fresh([
            'assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint', 'labels', 'checklists',
        ])->loadCount(['comments', 'attachments', 'predecessorDependencies']))]);
    }

    protected function quickAssign(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $assignee = ! empty($validated['assigned_to'])
            ? User::query()->find($validated['assigned_to'])
            : null;

        $updated = $this->tasks->assign($task, $assignee, $request->user());

        return response()->json(['data' => $this->board->cardPayload($updated->load([
            'assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint', 'labels', 'checklists',
        ])->loadCount(['comments', 'attachments', 'predecessorDependencies']))]);
    }

    protected function quickPriority(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        $validated = $request->validate([
            'priority' => ['required', 'string', 'max:32'],
            'priority_id' => ['nullable', 'integer', 'exists:task_priorities,id'],
        ]);

        $payload = ['priority' => $validated['priority']];
        if (! empty($validated['priority_id'])) {
            $payload['priority_id'] = $validated['priority_id'];
        }

        $updated = $this->tasks->update($task, $payload, $request->user());

        return response()->json(['data' => $this->board->cardPayload($updated->fresh([
            'assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint', 'labels', 'checklists',
        ])->loadCount(['comments', 'attachments', 'predecessorDependencies']))]);
    }

    protected function quickLogTime(Request $request, Task $task): JsonResponse
    {
        $this->authorize('updateOwnWork', $task);
        $validated = $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->timeTracking->logManual($task, [
            'duration_minutes' => (int) $validated['duration_minutes'],
            'description' => $validated['description'] ?? null,
            'source' => 'manual',
        ], $request->user());

        $task = $task->fresh([
            'assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint', 'labels', 'checklists',
        ])->loadCount(['comments', 'attachments', 'predecessorDependencies']);

        return response()->json(['data' => $this->board->cardPayload($task)]);
    }

    protected function quickChecklist(Request $request, Task $task): JsonResponse
    {
        $this->authorize('updateOwnWork', $task);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $this->checklists->create($task, ['title' => $validated['title']], $request->user());

        $task = $task->fresh([
            'assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint', 'labels', 'checklists',
        ])->loadCount(['comments', 'attachments', 'predecessorDependencies']);

        return response()->json(['data' => $this->board->cardPayload($task)]);
    }

    protected function quickComment(Request $request, Task $task): JsonResponse
    {
        $this->authorize('updateOwnWork', $task);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->comments->create($task, ['body' => $validated['body']], $request->user());

        $task = $task->fresh([
            'assignee', 'taskStatus', 'taskPriority', 'project', 'milestone', 'sprint', 'labels', 'checklists',
        ])->loadCount(['comments', 'attachments', 'predecessorDependencies']);

        return response()->json(['data' => $this->board->cardPayload($task)]);
    }
}
