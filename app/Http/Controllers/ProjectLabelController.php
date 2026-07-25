<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachLabelRequest;
use App\Http\Requests\StoreProjectLabelRequest;
use App\Http\Requests\UpdateProjectLabelRequest;
use App\Models\ProjectLabel;
use App\Models\Task;
use App\Services\ProjectLabelService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectLabelController extends Controller
{
    public function __construct(protected ProjectLabelService $labelService)
    {
        $this->authorizeResource(ProjectLabel::class, 'label');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $labels = $this->labelService->list($organization, [
            'search' => $request->string('search')->trim()->toString() ?: null,
        ]);

        return view('projects.labels.index', [
            'labels' => $labels,
            'organization' => $organization,
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('projects.labels.create', [
            'label' => new ProjectLabel(['color' => '#64748b']),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProjectLabelRequest $request, TenantContext $tenant): RedirectResponse
    {
        $this->labelService->create([
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ], $request->user());

        return redirect()
            ->route('project-labels.index')
            ->with('status', 'project-label-created');
    }

    public function edit(ProjectLabel $label): View
    {
        return view('projects.labels.edit', [
            'label' => $label,
        ]);
    }

    public function update(UpdateProjectLabelRequest $request, ProjectLabel $label): RedirectResponse
    {
        try {
            $this->labelService->update($label, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('project-labels.index')
            ->with('status', 'project-label-updated');
    }

    public function destroy(ProjectLabel $label, Request $request): RedirectResponse
    {
        try {
            $this->labelService->delete($label, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('project-labels.index')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('project-labels.index')
            ->with('status', 'project-label-deleted');
    }

    public function attach(AttachLabelRequest $request, Task $task, ProjectLabel $label): RedirectResponse
    {
        $this->labelService->attach($task, $label, $request->user());

        return redirect()
            ->back()
            ->with('status', 'task-label-attached');
    }

    public function detach(AttachLabelRequest $request, Task $task, ProjectLabel $label): RedirectResponse
    {
        $this->labelService->detach($task, $label, $request->user());

        return redirect()
            ->back()
            ->with('status', 'task-label-detached');
    }
}
