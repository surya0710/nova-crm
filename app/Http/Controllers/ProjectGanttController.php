<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\TimelineService;
use Illuminate\View\View;

class ProjectGanttController extends Controller
{
    public function __construct(protected TimelineService $timelineService) {}

    public function show(Project $project): View
    {
        $this->authorize('viewGantt', $project);

        $project->loadMissing(['milestones', 'status']);

        return view('projects.gantt.show', [
            'project' => $project,
            'ganttItems' => $this->timelineService->gantt($project),
            'timeline' => $this->timelineService->build($project),
        ]);
    }
}
