<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectDependencyRequest;
use App\Http\Requests\UpdateProjectDependencyRequest;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\ProjectDependency;
use App\Services\DependencyGraphService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectDependencyController extends Controller
{
    public function __construct(protected DependencyGraphService $dependencyService)
    {
        $this->authorizeResource(ProjectDependency::class, 'dependency', [
            'except' => ['index'],
        ]);
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', ProjectDependency::class);

        $portfolio = null;
        if ($portfolioId = $request->integer('portfolio_id')) {
            $portfolio = Portfolio::query()
                ->where('organization_id', $tenant->id())
                ->findOrFail($portfolioId);
        }

        $graph = $this->dependencyService->graph($tenant->id(), $portfolio);

        $dependencies = ProjectDependency::query()
            ->where('organization_id', $tenant->id())
            ->with(['predecessor', 'successor'])
            ->latest('id')
            ->paginate(25);

        return view('project-dependencies.index', [
            'graph' => $graph,
            'dependencies' => $dependencies,
            'portfolio' => $portfolio,
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProjectDependencyRequest $request, TenantContext $tenant): RedirectResponse
    {
        try {
            $this->dependencyService->create([
                ...$request->validated(),
                'organization_id' => $tenant->id(),
            ], $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('project-dependencies.index')
            ->with('status', 'project-dependency-created');
    }

    public function update(UpdateProjectDependencyRequest $request, ProjectDependency $dependency): RedirectResponse
    {
        try {
            $this->dependencyService->update($dependency, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('project-dependencies.index')
            ->with('status', 'project-dependency-updated');
    }

    public function destroy(ProjectDependency $dependency, Request $request): RedirectResponse
    {
        $this->dependencyService->delete($dependency, $request->user());

        return redirect()
            ->route('project-dependencies.index')
            ->with('status', 'project-dependency-deleted');
    }

    public function projectIndex(Project $project): View
    {
        $this->authorize('viewDependencies', $project);

        $impact = $this->dependencyService->impactAnalysis($project);

        return view('projects.dependencies.index', [
            'project' => $project,
            'impact' => $impact,
        ]);
    }
}
