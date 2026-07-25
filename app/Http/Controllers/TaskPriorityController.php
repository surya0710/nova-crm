<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskPriorityRequest;
use App\Http\Requests\UpdateTaskPriorityRequest;
use App\Models\TaskPriority;
use App\Services\TaskPriorityService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskPriorityController extends Controller
{
    public function __construct(protected TaskPriorityService $priorityService)
    {
        $this->authorizeResource(TaskPriority::class, 'priority');
    }

    public function index(TenantContext $tenant): View
    {
        return view('tasks.priorities.index', [
            'priorities' => TaskPriority::query()
                ->orderBy('level')
                ->orderBy('name')
                ->paginate(50),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('tasks.priorities.create', [
            'priority' => new TaskPriority(['is_default' => false, 'level' => 1]),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreTaskPriorityRequest $request, TenantContext $tenant): RedirectResponse
    {
        $this->priorityService->create($tenant->get(), $request->validated());

        return redirect()
            ->route('task-priorities.index')
            ->with('status', 'task-priority-created');
    }

    public function edit(TaskPriority $priority): View
    {
        return view('tasks.priorities.edit', [
            'priority' => $priority,
        ]);
    }

    public function update(UpdateTaskPriorityRequest $request, TaskPriority $priority): RedirectResponse
    {
        $this->priorityService->update($priority, $request->validated());

        return redirect()
            ->route('task-priorities.index')
            ->with('status', 'task-priority-updated');
    }

    public function destroy(TaskPriority $priority): RedirectResponse
    {
        $this->priorityService->delete($priority);

        return redirect()
            ->route('task-priorities.index')
            ->with('status', 'task-priority-deleted');
    }
}
