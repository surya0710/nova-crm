<?php

namespace App\Http\Controllers;

use App\Services\ExecutiveDashboardService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioExecutiveController extends Controller
{
    public function __construct(protected ExecutiveDashboardService $executiveService) {}

    public function show(Request $request, TenantContext $tenant): View
    {
        abort_unless($request->user()?->hasPermission('projects.executive.view'), 403);

        $organization = $tenant->get();

        return view('portfolios.executive.show', [
            'organization' => $organization,
            'dashboard' => $this->executiveService->forOrganization($organization, $request->user()),
        ]);
    }
}
