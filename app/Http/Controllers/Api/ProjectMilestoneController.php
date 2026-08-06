<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectMilestoneRequest;
use App\Http\Requests\UpdateProjectMilestoneRequest;
use App\Http\Resources\ProjectMilestoneResource;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\ProjectMilestoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectMilestoneController extends Controller
{
    public function __construct(protected ProjectMilestoneService $milestoneService) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $milestones = ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->orderBy('sequence')
            ->orderBy('id')
            ->paginate(request()->integer('per_page', 50));

        return ProjectMilestoneResource::collection($milestones);
    }

    public function store(StoreProjectMilestoneRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageMilestones', $project);

        try {
            $milestone = $this->milestoneService->create(
                $project,
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return (new ProjectMilestoneResource($milestone))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectMilestoneRequest $request, Project $project, ProjectMilestone $milestone): ProjectMilestoneResource|JsonResponse
    {
        $this->assertMilestoneBelongsToProject($project, $milestone);

        try {
            $milestone = $this->milestoneService->update(
                $milestone,
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProjectMilestoneResource($milestone);
    }

    public function destroy(Project $project, ProjectMilestone $milestone, Request $request): JsonResponse
    {
        $this->authorize('delete', $milestone);
        $this->assertMilestoneBelongsToProject($project, $milestone);

        try {
            $this->milestoneService->delete($milestone, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function complete(Request $request, Project $project, ProjectMilestone $milestone): ProjectMilestoneResource|JsonResponse
    {
        $this->assertMilestoneBelongsToProject($project, $milestone);
        $this->authorize('complete', $milestone);

        try {
            $milestone = $this->milestoneService->complete($milestone, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProjectMilestoneResource($milestone);
    }

    protected function assertMilestoneBelongsToProject(Project $project, ProjectMilestone $milestone): void
    {
        abort_unless((int) $milestone->project_id === (int) $project->id, 404);
    }
}
