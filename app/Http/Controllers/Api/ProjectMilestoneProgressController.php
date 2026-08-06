<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\MilestoneProgressService;
use Illuminate\Http\JsonResponse;

class ProjectMilestoneProgressController extends Controller
{
    public function __construct(protected MilestoneProgressService $milestoneProgressService) {}

    public function index(Project $project): JsonResponse
    {
        $this->authorize('viewProgress', $project);

        return response()->json([
            'data' => $this->milestoneProgressService->forProject($project),
        ]);
    }
}
