<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Services\Hrms\HrmsWorkspaceHomeService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class HrmsHomeController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, HrmsWorkspaceHomeService $home): View|RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasAnyPermission([
                'hrms.view',
                'hr.dashboard',
                'ess.access',
                'manager.dashboard',
                'employee.directory',
                'attendance.view',
                'leave.view',
                'recruitment.view',
                'payroll.view',
                'performance.view',
            ]),
            403
        );

        // Pure employee personas belong on ESS, not the HR admin workspace home.
        $isHrOperator = $user->hasAnyPermission([
            'hrms.view',
            'hr.dashboard',
            'manager.dashboard',
            'employee.directory',
            'attendance.view',
            'leave.view',
            'recruitment.view',
            'payroll.view',
            'performance.view',
        ]);

        if (! $isHrOperator && $user->hasPermission('ess.access') && Route::has('ess.dashboard')) {
            return redirect()->route('ess.dashboard');
        }

        $data = $home->build($user);

        return view('hrms.home', array_merge($data, [
            'organization' => $tenant->get(),
        ]));
    }
}
