<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskStatusRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\TaskStatus;
use App\Services\TaskStatusService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskStatusController extends Controller
{
    public function __construct(protected TaskStatusService $statusService)
    {
        $this->authorizeResource(TaskStatus::class, 'status');
    }

    public function index(TenantContext $tenant): View
    {
        return view('tasks.statuses.index', [
            'statuses' => TaskStatus::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(50),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('tasks.statuses.create', [
            'status' => new TaskStatus(['is_default' => false, 'is_closed' => false]),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreTaskStatusRequest $request, TenantContext $tenant): RedirectResponse
    {
        $this->statusService->create($tenant->get(), $request->validated());

        return redirect()
            ->route('task-statuses.index')
            ->with('status', 'task-status-created');
    }

    public function edit(TaskStatus $status): View
    {
        return view('tasks.statuses.edit', [
            'status' => $status,
        ]);
    }

    public function update(UpdateTaskStatusRequest $request, TaskStatus $status): RedirectResponse
    {
        $this->statusService->update($status, $request->validated());

        return redirect()
            ->route('task-statuses.index')
            ->with('status', 'task-status-updated');
    }

    public function destroy(TaskStatus $status): RedirectResponse
    {
        $this->statusService->delete($status);

        return redirect()
            ->route('task-statuses.index')
            ->with('status', 'task-status-deleted');
    }
}
