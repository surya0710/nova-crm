<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectIssueRequest;
use App\Http\Requests\UpdateProjectIssueRequest;
use App\Models\Project;
use App\Models\ProjectIssue;
use App\Services\IssueManagementService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectIssueController extends Controller
{
    public function __construct(protected IssueManagementService $issueService) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', ProjectIssue::class);

        $filters = [
            'project_id' => $request->integer('project_id') ?: null,
            'portfolio_id' => $request->integer('portfolio_id') ?: null,
            'program_id' => $request->integer('program_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'priority' => $request->string('priority')->toString() ?: null,
        ];

        return view('issues.index', [
            'issues' => $this->issueService->list($tenant->id(), $filters),
            'organization' => $tenant->get(),
            'filters' => $filters,
        ]);
    }

    public function projectIndex(Project $project): View
    {
        $this->authorize('viewIssues', $project);

        $issues = $this->issueService->list($project->organization_id, [
            'project_id' => $project->id,
        ]);

        return view('projects.issues.index', [
            'project' => $project,
            'issues' => $issues,
        ]);
    }

    public function store(StoreProjectIssueRequest $request, TenantContext $tenant, ?Project $project = null): RedirectResponse
    {
        $data = [
            ...$request->validated(),
            'organization_id' => $tenant->id(),
        ];

        $project ??= $this->resolveRouteProject($request);
        if ($project instanceof Project) {
            $this->authorize('createIssues', $project);
            $data['project_id'] = $project->id;
        } elseif (! empty($data['project_id'])) {
            $data['project_id'] = (int) $data['project_id'];
        }

        $this->issueService->create($data, $request->user());

        return redirect()
            ->back()
            ->with('status', 'project-issue-created');
    }

    public function update(UpdateProjectIssueRequest $request, ?Project $project = null, ?ProjectIssue $issue = null): RedirectResponse
    {
        $issue ??= $request->route('issue');
        abort_unless($issue instanceof ProjectIssue, 404);

        $project ??= $this->resolveRouteProject($request);
        if ($project instanceof Project) {
            $this->assertBelongsToProject($project, $issue);
        }

        try {
            $this->issueService->update($issue, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->back()
            ->with('status', 'project-issue-updated');
    }

    public function destroy(Request $request, ?Project $project = null, ?ProjectIssue $issue = null): RedirectResponse
    {
        $issue ??= $request->route('issue');
        abort_unless($issue instanceof ProjectIssue, 404);

        $this->authorize('delete', $issue);

        $project ??= $this->resolveRouteProject($request);
        if ($project instanceof Project) {
            $this->assertBelongsToProject($project, $issue);
        }

        $this->issueService->delete($issue, $request->user());

        return redirect()
            ->back()
            ->with('status', 'project-issue-deleted');
    }

    protected function assertBelongsToProject(Project $project, ProjectIssue $issue): void
    {
        abort_unless((int) $issue->project_id === (int) $project->id, 404);
    }

    protected function resolveRouteProject(Request $request): ?Project
    {
        $project = $request->route('project');

        if ($project instanceof Project) {
            return $project;
        }

        if (is_numeric($project)) {
            return Project::query()->find((int) $project);
        }

        return null;
    }
}
