<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Services\Administration\AdministrationWorkspaceHomeService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdministrationHomeController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, AdministrationWorkspaceHomeService $home): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'settings.manage',
                'users.view',
                'rbac.view',
                'workflows.view',
                'metadata.view',
                'metadata.manage',
                'integrations.view',
                'integrations.manage',
                'api.tokens',
                'audit.view',
            ]),
            403
        );

        $data = $home->build($request->user());

        return view('administration.home', array_merge($data, [
            'organization' => $tenant->get(),
        ]));
    }
}
