<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsWorkspaceHomeService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsHomeController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, AnalyticsWorkspaceHomeService $home): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'reports.view', 'finance.view', 'audit.view', 'projects.reports.view', 'recruitment.reports.view',
            ]),
            403
        );

        return view('analytics.home', array_merge($home->build($request->user()), [
            'organization' => $tenant->get(),
        ]));
    }
}
