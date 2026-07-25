<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CaptureProjectBaselineRequest;
use App\Http\Resources\ProjectBaselineResource;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Services\BaselineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectBaselineController extends Controller
{
    public function __construct(protected BaselineService $baselineService) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewBaselines', $project);

        $baselines = ProjectBaseline::query()
            ->where('project_id', $project->id)
            ->with('creator')
            ->orderByDesc('version')
            ->get();

        return ProjectBaselineResource::collection($baselines);
    }

    public function store(CaptureProjectBaselineRequest $request, Project $project): JsonResponse
    {
        $validated = $request->validated();

        $baseline = $this->baselineService->capture(
            $project,
            $request->user(),
            $validated['notes'] ?? null,
            $validated['name'] ?? null,
        );

        return (new ProjectBaselineResource($baseline->load('creator')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project, ProjectBaseline $baseline): JsonResponse
    {
        $this->authorize('viewBaselines', $project);
        abort_unless((int) $baseline->project_id === (int) $project->id, 404);

        return response()->json([
            'data' => [
                'baseline' => new ProjectBaselineResource($baseline->load('creator')),
                'comparison' => $this->baselineService->compare($baseline, $project),
            ],
        ]);
    }
}
