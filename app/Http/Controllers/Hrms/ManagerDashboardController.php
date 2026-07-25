<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HrmsDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerDashboardController extends Controller
{
    public function __invoke(Request $request, EssContext $essContext, HrmsDashboardService $dashboardService): View
    {
        abort_unless($request->user()?->hasPermission('manager.dashboard'), 403);

        $manager = $essContext->requireEmployee($request->user());

        return view('hrms.manager.dashboard', $dashboardService->managerDashboard($manager));
    }
}
