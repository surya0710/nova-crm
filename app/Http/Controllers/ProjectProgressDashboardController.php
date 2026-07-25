<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\MilestoneProgressService;
use App\Services\ProjectHealthService;
use App\Services\ProjectStatisticsService;
use App\Services\TimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectProgressDashboardController extends Controller
{
    public function __construct(
        protected ProjectHealthService $healthService,
        protected ProjectStatisticsService $statisticsService,
        protected MilestoneProgressService $milestoneProgressService,
        protected TimelineService $timelineService,
    ) {}

    public function index(Request $request, Project $project): View
    {
        $this->authorize('viewProgress', $project);

        $snapshot = $this->healthService->latest($project);

        if (! $snapshot) {
            $snapshot = $this->healthService->calculate($project, $request->user());
        }

        $project->load([
            'status',
            'manager',
            'owner',
            'members.user',
            'milestones' => fn ($q) => $q->orderBy('sequence'),
            'progressUpdates' => fn ($q) => $q->with('updater')->limit(5),
        ]);

        $statistics = $this->statisticsService->forProject($project);
        $milestoneProgress = $this->milestoneProgressService->forProject($project);
        $timeline = $this->timelineService->build($project);
        $overdueTasks = $this->healthService->detectOverdueTasks($project);
        $delayedMilestones = $this->healthService->detectDelayedMilestones($project);

        return view('projects.progress.dashboard', [
            'project' => $project,
            'snapshot' => $snapshot,
            'statistics' => $statistics,
            'milestoneProgress' => $milestoneProgress,
            'timeline' => $timeline,
            'overdueTasks' => $overdueTasks,
            'delayedMilestones' => $delayedMilestones,
            'healthStatuses' => config('projects.health_statuses', []),
        ]);
    }

    public function statistics(Project $project): View
    {
        $this->authorize('viewStatistics', $project);

        return view('projects.progress.dashboard', [
            'project' => $project->load(['status', 'manager']),
            'snapshot' => $this->healthService->latest($project),
            'statistics' => $this->statisticsService->forProject($project),
            'milestoneProgress' => $this->milestoneProgressService->forProject($project),
            'timeline' => $this->timelineService->build($project),
            'overdueTasks' => collect(),
            'delayedMilestones' => collect(),
            'healthStatuses' => config('projects.health_statuses', []),
            'statisticsOnly' => true,
        ]);
    }
}
