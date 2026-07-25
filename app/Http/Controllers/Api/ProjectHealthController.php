<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectHealthSnapshotResource;
use App\Models\Project;
use App\Services\ProjectHealthService;
use Illuminate\Http\Request;

class ProjectHealthController extends Controller
{
    public function __construct(protected ProjectHealthService $healthService) {}

    public function show(Request $request, Project $project): ProjectHealthSnapshotResource
    {
        $this->authorize('viewHealth', $project);

        $snapshot = $this->healthService->latest($project);

        if (! $snapshot || $request->boolean('recalculate')) {
            $snapshot = $this->healthService->calculate($project, $request->user());
        }

        return new ProjectHealthSnapshotResource($snapshot->load('project'));
    }
}
