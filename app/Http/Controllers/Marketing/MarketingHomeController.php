<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MarketingWorkspaceHomeService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingHomeController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, MarketingWorkspaceHomeService $home): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'marketing.view',
                'marketing.manage',
                'integrations.view',
                'integrations.manage',
            ]),
            403
        );

        return view('marketing.home', array_merge($home->build($request->user()), [
            'organization' => $tenant->get(),
        ]));
    }
}
