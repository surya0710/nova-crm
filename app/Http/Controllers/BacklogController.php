<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskPriority;
use App\Services\BacklogService;
use App\Services\SprintService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BacklogController extends Controller
{
    public function __construct(
        protected BacklogService $backlog,
        protected SprintService $sprints,
    ) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', Task::class);

        $organization = $tenant->get();
        $filters = $request->only(['project_id', 'sprint_id', 'unscheduled']);
        if ($request->boolean('unscheduled') || ($filters['sprint_id'] ?? null) === 'none') {
            $filters['sprint_id'] = 'none';
        }

        return view('tasks.backlog.index', [
            'tasks' => $this->backlog->list($organization, $filters),
            'filters' => $filters,
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'sprints' => $this->sprints->forOrganization($organization, $request->integer('project_id') ?: null),
            'milestones' => ProjectMilestone::query()->orderBy('name')->limit(200)->get(['id', 'name', 'project_id']),
            'assignees' => $organization->users()->orderBy('name')->limit(200)->get(['users.id', 'users.name']),
            'priorities' => config('tasks.priorities', []),
            'catalogPriorities' => TaskPriority::query()->orderBy('level')->get(),
        ]);
    }

    public function reorder(Request $request, TenantContext $tenant): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:tasks,id'],
        ]);

        $this->backlog->reorder($tenant->get(), $validated['task_ids'], $request->user());

        return response()->json(['success' => true]);
    }

    public function move(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
            'milestone_id' => ['nullable', 'integer', 'exists:project_milestones,id'],
        ]);

        if (array_key_exists('sprint_id', $validated)) {
            $sprint = $validated['sprint_id']
                ? Sprint::query()->findOrFail($validated['sprint_id'])
                : null;
            $task = $this->backlog->moveToSprint($task, $sprint, $request->user());
        }

        if (array_key_exists('milestone_id', $validated)) {
            $milestone = $validated['milestone_id']
                ? ProjectMilestone::query()->findOrFail($validated['milestone_id'])
                : null;
            $task = $this->backlog->moveToMilestone($task, $milestone, $request->user());
        }

        return response()->json([
            'data' => [
                'id' => $task->id,
                'sprint_id' => $task->sprint_id,
                'milestone_id' => $task->milestone_id,
            ],
        ]);
    }

    public function bulk(Request $request, TenantContext $tenant): RedirectResponse
    {
        $this->authorize('viewAny', Task::class);

        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer', 'exists:tasks,id'],
            'action' => ['required', 'in:assign,priority'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', 'string', 'max:32'],
        ]);

        $count = match ($validated['action']) {
            'assign' => $this->backlog->bulkAssign(
                $tenant->get(),
                $validated['task_ids'],
                $validated['assigned_to'] ?? null,
                $request->user(),
            ),
            'priority' => $this->backlog->bulkPriority(
                $tenant->get(),
                $validated['task_ids'],
                (string) ($validated['priority'] ?? 'medium'),
                $request->user(),
            ),
        };

        return redirect()
            ->back()
            ->with('status', 'backlog-bulk-updated')
            ->with('bulk_count', $count);
    }
}
