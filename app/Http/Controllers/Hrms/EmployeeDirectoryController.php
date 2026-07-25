<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Hrms\EmployeeDirectoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeDirectoryController extends Controller
{
    public function index(Request $request, EmployeeDirectoryService $directoryService): View
    {
        abort_unless($request->user()?->hasPermission('employee.directory'), 403);

        return view('hrms.directory.index', [
            'employees' => $directoryService->search($request->only(['q', 'department_id', 'designation_id', 'branch_id', 'team_id'])),
            'filters' => $directoryService->filterOptions(),
        ]);
    }

    public function show(Request $request, Employee $employee, EmployeeDirectoryService $directoryService): View
    {
        abort_unless($request->user()?->hasPermission('employee.directory'), 403);
        $this->authorize('view', $employee);

        return view('hrms.directory.show', [
            'profile' => $directoryService->profileCard($employee),
            'employee' => $employee,
        ]);
    }
}
