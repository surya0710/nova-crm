<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectIssueRequest;
use App\Http\Requests\UpdateProjectIssueRequest;
use App\Http\Resources\ProjectIssueResource;
use App\Models\Project;
use App\Models\ProjectIssue;
use App\Services\IssueManagementService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectIssueController extends Controller
{
    public function __construct(protected IssueManagementService $issueService) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProjectIssue::class);

        $filters = [
            'project_id' => $request->integer('project_id') ?: null,
            'portfolio_id' => $request->integer('portfolio_id') ?: null,
            'program_id' => $request->integer('program_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'priority' => $request->string('priority')->toString() ?: null,
        ];

        return ProjectIssueResource::collection(
            $this->issueService->list($tenant->id(), $filters)
        );
    }

    public function projectIndex(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewIssues', $project);

        return ProjectIssueResource::collection(
            $this->issueService->list($project->organization_id, ['project_id' => $project->id])
        );
    }

    public function store(StoreProjectIssueRequest $request, TenantContext $tenant): JsonResponse
    {
        $data = [
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ];

        $project = $request->route('project');
        if ($project instanceof Project) {
            $this->authorize('createIssues', $project);
            $data['project_id'] = $project->id;
        }

        $issue = $this->issueService->create($data, $request->user());

        return (new ProjectIssueResource($issue->load(['owner', 'project'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectIssueRequest $request, ProjectIssue $issue): ProjectIssueResource|JsonResponse
    {
        $project = $request->route('project');
        if ($project instanceof Project) {
            abort_unless((int) $issue->project_id === (int) $project->id, 404);
        }

        try {
            $issue = $this->issueService->update($issue, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProjectIssueResource($issue->load(['owner', 'project']));
    }

    public function destroy(Request $request, ProjectIssue $issue): JsonResponse
    {
        $this->authorize('delete', $issue);

        $project = $request->route('project');
        if ($project instanceof Project) {
            abort_unless((int) $issue->project_id === (int) $project->id, 404);
        }

        $this->issueService->delete($issue, $request->user());

        return response()->json(['success' => true]);
    }
}
