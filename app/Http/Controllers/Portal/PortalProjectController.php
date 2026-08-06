<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Services\ClientPortalFacadeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalProjectController extends Controller
{
    public function __construct(protected ClientPortalFacadeService $facade) {}

    public function show(Request $request, Organization $organization, Project $project): View
    {
        abort_unless((int) $project->organization_id === (int) $organization->id, 404);

        $payload = $this->facade->project($request->user('client'), $project);

        return view('portal.projects.show', [
            'payload' => $payload,
            'project' => $project,
        ]);
    }
}
