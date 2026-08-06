<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRiskRequest;
use App\Http\Requests\UpdateProjectRiskRequest;
use App\Models\Project;
use App\Models\ProjectRisk;
use App\Services\RiskManagementService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectRiskController extends Controller
{
    public function __construct(protected RiskManagementService $riskService) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', ProjectRisk::class);

        $filters = [
            'project_id' => $request->integer('project_id') ?: null,
            'portfolio_id' => $request->integer('portfolio_id') ?: null,
            'program_id' => $request->integer('program_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        return view('risks.index', [
            'risks' => $this->riskService->list($tenant->id(), $filters),
            'matrix' => $this->riskService->matrix(
                $tenant->id(),
                $filters['project_id'],
                $filters['portfolio_id'],
            ),
            'organization' => $tenant->get(),
            'filters' => $filters,
        ]);
    }

    public function projectIndex(Project $project): View
    {
        $this->authorize('viewRisks', $project);

        $risks = $this->riskService->list($project->organization_id, [
            'project_id' => $project->id,
        ]);

        return view('projects.risks.index', [
            'project' => $project,
            'risks' => $risks,
            'matrix' => $this->riskService->matrix($project->organization_id, $project->id),
        ]);
    }

    public function store(StoreProjectRiskRequest $request, TenantContext $tenant, ?Project $project = null): RedirectResponse
    {
        $data = [
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ];

        $project ??= $this->resolveRouteProject($request);
        if ($project instanceof Project) {
            $this->authorize('createRisks', $project);
            $data['project_id'] = $project->id;
        } elseif (! empty($data['project_id'])) {
            $data['project_id'] = (int) $data['project_id'];
        }

        $this->riskService->create($data, $request->user());

        return redirect()
            ->back()
            ->with('status', 'project-risk-created');
    }

    public function update(UpdateProjectRiskRequest $request, ?Project $project = null, ?ProjectRisk $risk = null): RedirectResponse
    {
        $risk ??= $request->route('risk');
        abort_unless($risk instanceof ProjectRisk, 404);

        $project ??= $this->resolveRouteProject($request);
        if ($project instanceof Project) {
            $this->assertBelongsToProject($project, $risk);
        }

        try {
            $this->riskService->update($risk, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->back()
            ->with('status', 'project-risk-updated');
    }

    public function destroy(Request $request, ?Project $project = null, ?ProjectRisk $risk = null): RedirectResponse
    {
        $risk ??= $request->route('risk');
        abort_unless($risk instanceof ProjectRisk, 404);

        $this->authorize('delete', $risk);

        $project ??= $this->resolveRouteProject($request);
        if ($project instanceof Project) {
            $this->assertBelongsToProject($project, $risk);
        }

        $this->riskService->delete($risk, $request->user());

        return redirect()
            ->back()
            ->with('status', 'project-risk-deleted');
    }

    protected function assertBelongsToProject(Project $project, ProjectRisk $risk): void
    {
        abort_unless((int) $risk->project_id === (int) $project->id, 404);
    }

    protected function resolveRouteProject(Request $request): ?Project
    {
        $project = $request->route('project');

        if ($project instanceof Project) {
            return $project;
        }

        if (is_numeric($project)) {
            return Project::query()->find((int) $project);
        }

        return null;
    }
}
