<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Services\Hrms\LeaveService;
use Illuminate\View\View;

class LeaveDashboardController extends Controller
{
    public function __invoke(LeaveService $service): View
    {
        $this->authorize('viewAny', LeaveApplication::class);

        return view('hrms.leave.dashboard', [
            'stats' => $service->dashboardStats(),
            'recentApplications' => LeaveApplication::query()
                ->with(['employee', 'leaveType'])
                ->latest('submitted_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
