<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgressUpdateRequest;
use App\Http\Requests\UpdateProgressUpdateRequest;
use App\Http\Resources\ProgressUpdateResource;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Services\ProgressTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectProgressController extends Controller
{
    public function __construct(protected ProgressTrackingService $progressService) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewProgress', $project);

        $updates = $this->progressService->list(
            $project,
            request()->integer('per_page', 15),
        );

        return ProgressUpdateResource::collection($updates);
    }

    public function store(StoreProgressUpdateRequest $request, Project $project): JsonResponse
    {
        try {
            $update = $this->progressService->create(
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

        return (new ProgressUpdateResource($update->load(['updater', 'milestone', 'project'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateProgressUpdateRequest $request,
        Project $project,
        ProgressUpdate $progressUpdate,
    ): ProgressUpdateResource|JsonResponse {
        $this->assertProgressUpdateBelongsToProject($project, $progressUpdate);

        try {
            $update = $this->progressService->update(
                $progressUpdate,
                $request->validated(),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return new ProgressUpdateResource($update->load(['updater', 'milestone', 'project']));
    }

    public function destroy(Project $project, ProgressUpdate $progressUpdate, Request $request): JsonResponse
    {
        $this->authorize('deleteProgress', $project);
        $this->assertProgressUpdateBelongsToProject($project, $progressUpdate);

        try {
            $this->progressService->delete($progressUpdate, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    protected function assertProgressUpdateBelongsToProject(Project $project, ProgressUpdate $progressUpdate): void
    {
        abort_unless((int) $progressUpdate->project_id === (int) $project->id, 404);
    }
}
