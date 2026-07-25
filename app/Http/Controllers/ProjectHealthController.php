<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectHealthService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectHealthController extends Controller
{
    public function __construct(protected ProjectHealthService $healthService) {}

    public function show(Request $request, Project $project): View
    {
        $this->authorize('viewHealth', $project);

        $snapshot = $this->healthService->latest($project);

        if (! $snapshot || $request->boolean('recalculate')) {
            $snapshot = $this->healthService->calculate($project, $request->user());
        }

        $history = $project->healthSnapshots()
            ->limit(10)
            ->get();

        return view('projects.health.show', [
            'project' => $project->load(['status', 'manager', 'owner']),
            'snapshot' => $snapshot,
            'history' => $history,
            'healthStatuses' => config('projects.health_statuses', []),
        ]);
    }
}
