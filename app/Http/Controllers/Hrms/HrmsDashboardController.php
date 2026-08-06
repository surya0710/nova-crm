<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Services\Hrms\HrmsDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrmsDashboardController extends Controller
{
    public function __invoke(Request $request, HrmsDashboardService $dashboardService): View
    {
        abort_unless($request->user()?->hasPermission('hr.dashboard'), 403);

        return view('hrms.dashboard', $dashboardService->hrDashboard());
    }
}
