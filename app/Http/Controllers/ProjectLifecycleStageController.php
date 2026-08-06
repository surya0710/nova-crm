<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectLifecycleStageRequest;
use App\Http\Requests\UpdateProjectLifecycleStageRequest;
use App\Models\ProjectLifecycleStage;
use App\Services\ProjectLifecycleService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectLifecycleStageController extends Controller
{
    public function __construct(protected ProjectLifecycleService $lifecycleService)
    {
        $this->authorizeResource(ProjectLifecycleStage::class, 'stage');
    }

    public function index(TenantContext $tenant): View
    {
        return view('projects.lifecycle-stages.index', [
            'stages' => ProjectLifecycleStage::query()
                ->orderBy('sequence')
                ->orderBy('name')
                ->paginate(50),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('projects.lifecycle-stages.create', [
            'stage' => new ProjectLifecycleStage(['is_default' => false]),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProjectLifecycleStageRequest $request, TenantContext $tenant): RedirectResponse
    {
        $stage = $this->lifecycleService->create(
            $tenant->get(),
            $request->validated(),
        );

        return redirect()
            ->route('project-lifecycle-stages.index')
            ->with('status', 'project-lifecycle-stage-created');
    }

    public function edit(ProjectLifecycleStage $stage): View
    {
        return view('projects.lifecycle-stages.edit', [
            'stage' => $stage,
        ]);
    }

    public function update(UpdateProjectLifecycleStageRequest $request, ProjectLifecycleStage $stage): RedirectResponse
    {
        $this->lifecycleService->update($stage, $request->validated());

        return redirect()
            ->route('project-lifecycle-stages.index')
            ->with('status', 'project-lifecycle-stage-updated');
    }

    public function destroy(ProjectLifecycleStage $stage): RedirectResponse
    {
        $this->lifecycleService->delete($stage);

        return redirect()
            ->route('project-lifecycle-stages.index')
            ->with('status', 'project-lifecycle-stage-deleted');
    }
}
