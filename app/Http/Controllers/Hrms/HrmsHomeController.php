<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Services\Hrms\HrmsWorkspaceHomeService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrmsHomeController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant, HrmsWorkspaceHomeService $home): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
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

        $data = $home->build($request->user());

        return view('hrms.home', array_merge($data, [
            'organization' => $tenant->get(),
        ]));
    }
}
