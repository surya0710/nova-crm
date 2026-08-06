<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectStatisticsService;
use Illuminate\Http\JsonResponse;

class ProjectStatisticsController extends Controller
{
    public function __construct(protected ProjectStatisticsService $statisticsService) {}

    public function show(Project $project): JsonResponse
    {
        $this->authorize('viewStatistics', $project);

        return response()->json([
            'data' => $this->statisticsService->forProject($project),
        ]);
    }
}
