<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Services\Projects\ProjectsWorkspaceHomeService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectsHomeController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, ProjectsWorkspaceHomeService $home): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'projects.view',
                'resources.view',
                'projects.portfolios.view',
                'projects.programs.view',
                'tasks.view',
            ]),
            403
        );

        $data = $home->build($request->user());

        return view('projects.home', array_merge($data, [
            'organization' => $tenant->get(),
        ]));
    }
}
