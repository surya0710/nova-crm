<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\AssignSalaryStructureRequest;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\SalaryStructure;
use App\Services\Hrms\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeSalaryAssignmentController extends Controller
{
    public function __construct(protected PayrollService $service)
    {
        $this->authorizeResource(EmployeeSalaryAssignment::class, 'assignment');
    }

    public function index(): View
    {
        return view('hrms.payroll.assignments.index', [
            'assignments' => EmployeeSalaryAssignment::query()
                ->with(['employee', 'salaryStructure', 'assignedBy'])
                ->latest('effective_from')
                ->paginate(20),
            'employees' => Employee::query()
                ->whereIn('status', config('hrms.leave_applicable_employee_statuses', ['active']))
                ->orderBy('first_name')
                ->get(),
            'structures' => SalaryStructure::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(AssignSalaryStructureRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        $this->service->assignSalaryStructure($employee, $request->validated(), $request->user());

        return redirect()->route('hrms.payroll.assignments.index')
            ->with('status', 'hrms-employee-salary-assigned');
    }
}
