<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSprintRequest;
use App\Http\Requests\UpdateSprintRequest;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\SprintService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SprintController extends Controller
{
    public function __construct(protected SprintService $sprints) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', Sprint::class);

        $organization = $tenant->get();
        $projectId = $request->integer('project_id') ?: null;

        return view('tasks.sprints.index', [
            'sprints' => $this->sprints->forOrganization($organization, $projectId),
            'projects' => Project::query()->where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'filters' => ['project_id' => $projectId],
            'statuses' => config('tasks.sprint_statuses', []),
        ]);
    }

    public function store(StoreSprintRequest $request, TenantContext $tenant): RedirectResponse
    {
        $this->authorize('create', Sprint::class);

        $sprint = $this->sprints->create($tenant->get(), $request->validated(), $request->user());

        return redirect()
            ->route('sprints.index', array_filter(['project_id' => $sprint->project_id]))
            ->with('status', 'sprint-created');
    }

    public function update(UpdateSprintRequest $request, Sprint $sprint): RedirectResponse
    {
        $this->authorize('update', $sprint);

        $this->sprints->update($sprint, $request->validated(), $request->user());

        return redirect()
            ->route('sprints.index', array_filter(['project_id' => $sprint->project_id]))
            ->with('status', 'sprint-updated');
    }

    public function destroy(Request $request, Sprint $sprint): RedirectResponse
    {
        $this->authorize('delete', $sprint);
        $projectId = $sprint->project_id;
        $this->sprints->delete($sprint, $request->user());

        return redirect()
            ->route('sprints.index', array_filter(['project_id' => $projectId]))
            ->with('status', 'sprint-deleted');
    }
}
