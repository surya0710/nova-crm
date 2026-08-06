<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Services\Hrms\PayrollService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryRevisionController extends Controller
{
    public function __construct(protected PayrollService $payrollService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeSalaryAssignment::class);

        $employeeId = $request->integer('employee_id') ?: null;
        $employee = $employeeId
            ? Employee::query()->findOrFail($employeeId)
            : null;

        $history = $employee
            ? $this->payrollService->salaryRevisionHistory($employee)
            : collect();

        $comparison = null;
        if ($history->count() >= 2) {
            $comparison = $this->payrollService->compareSalaryAssignments(
                $history->first(),
                $history->skip(1)->first(),
            );
        }

        return view('hrms.payroll.revisions.index', [
            'employees' => Employee::query()->orderBy('first_name')->limit(500)->get(),
            'employee' => $employee,
            'history' => $history,
            'comparison' => $comparison,
            'timeline' => $history,
        ]);
    }
}
