<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgressUpdateRequest;
use App\Http\Requests\UpdateProgressUpdateRequest;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Services\ProgressTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectProgressController extends Controller
{
    public function __construct(protected ProgressTrackingService $progressService) {}

    public function index(Project $project): View
    {
        $this->authorize('viewProgress', $project);

        $project->load(['milestones' => fn ($q) => $q->orderBy('sequence')]);

        $updates = $this->progressService->list($project, 20);

        return view('projects.progress.index', [
            'project' => $project,
            'updates' => $updates,
        ]);
    }

    public function store(StoreProgressUpdateRequest $request, Project $project): RedirectResponse
    {
        $this->progressService->create(
            $project,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('projects.progress.index', $project)
            ->with('status', 'progress-update-created');
    }

    public function update(
        UpdateProgressUpdateRequest $request,
        Project $project,
        ProgressUpdate $progressUpdate,
    ): RedirectResponse {
        $this->assertProgressUpdateBelongsToProject($project, $progressUpdate);

        $this->progressService->update(
            $progressUpdate,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('projects.progress.index', $project)
            ->with('status', 'progress-update-updated');
    }

    public function destroy(Project $project, ProgressUpdate $progressUpdate, Request $request): RedirectResponse
    {
        $this->authorize('deleteProgress', $project);
        $this->assertProgressUpdateBelongsToProject($project, $progressUpdate);

        $this->progressService->delete($progressUpdate, $request->user());

        return redirect()
            ->route('projects.progress.index', $project)
            ->with('status', 'progress-update-deleted');
    }

    protected function assertProgressUpdateBelongsToProject(Project $project, ProgressUpdate $progressUpdate): void
    {
        abort_unless((int) $progressUpdate->project_id === (int) $project->id, 404);
    }
}
