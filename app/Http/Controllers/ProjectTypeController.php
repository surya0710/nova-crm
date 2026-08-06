<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectTypeRequest;
use App\Http\Requests\UpdateProjectTypeRequest;
use App\Models\ProjectType;
use App\Services\ProjectTypeService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectTypeController extends Controller
{
    public function __construct(protected ProjectTypeService $typeService)
    {
        $this->authorizeResource(ProjectType::class, 'type');
    }

    public function index(TenantContext $tenant): View
    {
        return view('projects.types.index', [
            'types' => ProjectType::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(50),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('projects.types.create', [
            'type' => new ProjectType(['is_active' => true]),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProjectTypeRequest $request, TenantContext $tenant): RedirectResponse
    {
        $type = $this->typeService->create(
            $tenant->get(),
            $request->validated(),
        );

        return redirect()
            ->route('project-types.index')
            ->with('status', 'project-type-created');
    }

    public function edit(ProjectType $type): View
    {
        return view('projects.types.edit', [
            'type' => $type,
        ]);
    }

    public function update(UpdateProjectTypeRequest $request, ProjectType $type): RedirectResponse
    {
        $this->typeService->update($type, $request->validated());

        return redirect()
            ->route('project-types.index')
            ->with('status', 'project-type-updated');
    }

    public function destroy(ProjectType $type): RedirectResponse
    {
        $this->typeService->delete($type);

        return redirect()
            ->route('project-types.index')
            ->with('status', 'project-type-deleted');
    }
}
