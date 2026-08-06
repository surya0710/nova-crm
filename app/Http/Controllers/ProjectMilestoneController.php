<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectMilestoneRequest;
use App\Http\Requests\UpdateProjectMilestoneRequest;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\ProjectMilestoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectMilestoneController extends Controller
{
    public function __construct(protected ProjectMilestoneService $milestoneService) {}

    public function index(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['milestones' => fn ($q) => $q->orderBy('sequence')->orderBy('id')]);

        $progress = collect(app(\App\Services\MilestoneProgressService::class)->forProject($project))
            ->keyBy('milestone_id');

        return view('projects.milestones.index', [
            'project' => $project,
            'milestones' => $project->milestones,
            'milestoneProgress' => $progress,
            'milestoneStatuses' => config('projects.milestone_statuses'),
        ]);
    }

    public function store(StoreProjectMilestoneRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('manageMilestones', $project);

        $this->milestoneService->create(
            $project,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('projects.milestones.index', $project)
            ->with('status', 'project-milestone-created');
    }

    public function update(UpdateProjectMilestoneRequest $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        $this->assertMilestoneBelongsToProject($project, $milestone);

        $this->milestoneService->update(
            $milestone,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('projects.milestones.index', $project)
            ->with('status', 'project-milestone-updated');
    }

    public function destroy(Project $project, ProjectMilestone $milestone, Request $request): RedirectResponse
    {
        $this->authorize('delete', $milestone);
        $this->assertMilestoneBelongsToProject($project, $milestone);

        $this->milestoneService->delete($milestone, $request->user());

        return redirect()
            ->route('projects.milestones.index', $project)
            ->with('status', 'project-milestone-deleted');
    }

    public function complete(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        $this->assertMilestoneBelongsToProject($project, $milestone);
        $this->authorize('complete', $milestone);

        $this->milestoneService->complete($milestone, $request->user());

        return redirect()
            ->route('projects.milestones.index', $project)
            ->with('status', 'project-milestone-completed');
    }

    protected function assertMilestoneBelongsToProject(Project $project, ProjectMilestone $milestone): void
    {
        abort_unless((int) $milestone->project_id === (int) $project->id, 404);
    }
}
