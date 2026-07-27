<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\Operations\OperationsWorkspaceHomeService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsHomeController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, OperationsWorkspaceHomeService $home): View
    {
        abort_unless($request->user()->hasPermission('tasks.view'), 403);

        $data = $home->build($request->user());

        return view('operations.home', array_merge($data, [
            'organization' => $tenant->get(),
        ]));
    }
}
