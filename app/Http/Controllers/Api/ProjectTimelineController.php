<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\TimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTimelineController extends Controller
{
    public function __construct(protected TimelineService $timelineService) {}

    public function show(Project $project): JsonResponse
    {
        $this->authorize('viewTimeline', $project);

        return response()->json([
            'data' => $this->timelineService->build($project),
        ]);
    }

    public function gantt(Project $project): JsonResponse
    {
        $this->authorize('viewGantt', $project);

        $project->loadMissing(['milestones', 'status']);

        return response()->json([
            'data' => $this->timelineService->gantt($project),
        ]);
    }
}
