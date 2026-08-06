<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectStatusRequest;
use App\Http\Requests\UpdateProjectStatusRequest;
use App\Models\ProjectStatus;
use App\Services\ProjectStatusService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectStatusController extends Controller
{
    public function __construct(protected ProjectStatusService $statusService)
    {
        $this->authorizeResource(ProjectStatus::class, 'status');
    }

    public function index(TenantContext $tenant): View
    {
        return view('projects.statuses.index', [
            'statuses' => ProjectStatus::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(50),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('projects.statuses.create', [
            'status' => new ProjectStatus(['is_default' => false, 'is_closed' => false]),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProjectStatusRequest $request, TenantContext $tenant): RedirectResponse
    {
        $status = $this->statusService->create(
            $tenant->get(),
            $request->validated(),
        );

        return redirect()
            ->route('project-statuses.index')
            ->with('status', 'project-status-created');
    }

    public function edit(ProjectStatus $status): View
    {
        return view('projects.statuses.edit', [
            'status' => $status,
        ]);
    }

    public function update(UpdateProjectStatusRequest $request, ProjectStatus $status): RedirectResponse
    {
        $this->statusService->update($status, $request->validated());

        return redirect()
            ->route('project-statuses.index')
            ->with('status', 'project-status-updated');
    }

    public function destroy(ProjectStatus $status): RedirectResponse
    {
        $this->statusService->delete($status);

        return redirect()
            ->route('project-statuses.index')
            ->with('status', 'project-status-deleted');
    }
}
