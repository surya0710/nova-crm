<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HrmsDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EssDashboardController extends Controller
{
    public function __invoke(Request $request, EssContext $essContext, HrmsDashboardService $dashboardService): View
    {
        abort_unless($request->user()?->hasPermission('ess.access'), 403);

        $employee = $essContext->requireEmployee($request->user());

        return view('ess.dashboard', $dashboardService->employeeDashboard($employee));
    }
}
