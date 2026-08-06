<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRiskRequest;
use App\Http\Requests\UpdateProjectRiskRequest;
use App\Http\Resources\ProjectRiskResource;
use App\Models\Project;
use App\Models\ProjectRisk;
use App\Services\RiskManagementService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectRiskController extends Controller
{
    public function __construct(protected RiskManagementService $riskService) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewAny', ProjectRisk::class);

        $filters = [
            'project_id' => $request->integer('project_id') ?: null,
            'portfolio_id' => $request->integer('portfolio_id') ?: null,
            'program_id' => $request->integer('program_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        $risks = $this->riskService->list($tenant->id(), $filters);

        if ($request->boolean('include_matrix')) {
            return response()->json([
                'data' => ProjectRiskResource::collection($risks)->resolve(),
                'matrix' => $this->riskService->matrix(
                    $tenant->id(),
                    $filters['project_id'],
                    $filters['portfolio_id'],
                ),
            ]);
        }

        return ProjectRiskResource::collection($risks);
    }

    public function projectIndex(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewRisks', $project);

        return ProjectRiskResource::collection(
            $this->riskService->list($project->organization_id, ['project_id' => $project->id])
        );
    }

    public function store(StoreProjectRiskRequest $request, TenantContext $tenant): JsonResponse
    {
        $data = [
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ];

        $project = $request->route('project');
        if ($project instanceof Project) {
            $this->authorize('createRisks', $project);
            $data['project_id'] = $project->id;
        }

        $risk = $this->riskService->create($data, $request->user());

        return (new ProjectRiskResource($risk->load(['owner', 'project'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectRiskRequest $request, ProjectRisk $risk): ProjectRiskResource|JsonResponse
    {
        $project = $request->route('project');
        if ($project instanceof Project) {
            abort_unless((int) $risk->project_id === (int) $project->id, 404);
        }

        try {
            $risk = $this->riskService->update($risk, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProjectRiskResource($risk->load(['owner', 'project']));
    }

    public function destroy(Request $request, ProjectRisk $risk): JsonResponse
    {
        $this->authorize('delete', $risk);

        $project = $request->route('project');
        if ($project instanceof Project) {
            abort_unless((int) $risk->project_id === (int) $project->id, 404);
        }

        $this->riskService->delete($risk, $request->user());

        return response()->json(['success' => true]);
    }
}
