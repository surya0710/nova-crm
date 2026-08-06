<?php

namespace App\Http\Controllers;

use App\Http\Requests\PinCollaborationRequest;
use App\Models\Project;
use App\Models\ProjectCollaborationPin;
use App\Services\CollaborationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectCollaborationController extends Controller
{
    public function __construct(protected CollaborationService $collaborationService) {}

    public function show(Request $request, Project $project): View
    {
        $this->authorize('viewCollaboration', $project);

        $feed = $this->collaborationService->feed($project, [
            'limit' => $request->integer('limit', 50),
        ]);

        return view('projects.collaboration.show', [
            'project' => $project,
            'feed' => $feed,
        ]);
    }

    public function pin(PinCollaborationRequest $request, Project $project): RedirectResponse
    {
        $this->collaborationService->pin($project, $request->validated(), $request->user());

        return redirect()
            ->route('projects.collaboration.show', $project)
            ->with('status', 'collaboration-pin-created');
    }

    public function unpin(Request $request, Project $project, ProjectCollaborationPin $pin): RedirectResponse
    {
        $this->authorize('manageCollaboration', $project);
        abort_unless((int) $pin->project_id === (int) $project->id, 404);

        $this->collaborationService->unpinById($pin, $request->user());

        return redirect()
            ->route('projects.collaboration.show', $project)
            ->with('status', 'collaboration-pin-removed');
    }
}
