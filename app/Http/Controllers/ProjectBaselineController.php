<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaptureProjectBaselineRequest;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Services\BaselineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectBaselineController extends Controller
{
    public function __construct(protected BaselineService $baselineService) {}

    public function index(Project $project): View
    {
        $this->authorize('viewBaselines', $project);

        $baselines = ProjectBaseline::query()
            ->where('project_id', $project->id)
            ->with('creator')
            ->orderByDesc('version')
            ->get();

        return view('projects.baselines.index', [
            'project' => $project,
            'baselines' => $baselines,
        ]);
    }

    public function store(CaptureProjectBaselineRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        $this->baselineService->capture(
            $project,
            $request->user(),
            $validated['notes'] ?? null,
            $validated['name'] ?? null,
        );

        return redirect()
            ->route('projects.baselines.index', $project)
            ->with('status', 'project-baseline-captured');
    }

    public function show(Project $project, ProjectBaseline $baseline): View
    {
        $this->authorize('viewBaselines', $project);
        $this->assertBelongsToProject($project, $baseline);

        return view('projects.baselines.show', [
            'project' => $project,
            'baseline' => $baseline->load('creator'),
            'comparison' => $this->baselineService->compare($baseline, $project),
        ]);
    }

    protected function assertBelongsToProject(Project $project, ProjectBaseline $baseline): void
    {
        abort_unless((int) $baseline->project_id === (int) $project->id, 404);
    }
}
