<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\StoreEmployeeWfhAssignmentRequest;
use App\Http\Requests\Hrms\UpdateEmployeeWfhAssignmentRequest;
use App\Models\Employee;
use App\Models\EmployeeWfhAssignment;
use App\Models\Organization;
use App\Services\Hrms\WfhPolicyService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeWfhAssignmentController extends Controller
{
    public function __construct(protected WfhPolicyService $service)
    {
        $this->authorizeResource(EmployeeWfhAssignment::class, 'assignment');
    }

    public function index(TenantContext $tenant): View
    {
        $organization = Organization::query()->findOrFail($tenant->id());

        return view('hrms.wfh.assignments.index', [
            'assignments' => EmployeeWfhAssignment::query()
                ->with(['employee', 'assignedBy'])
                ->orderByDesc('is_active')
                ->orderByDesc('effective_from')
                ->paginate(15),
            'employees' => Employee::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'policyTypes' => collect(config('hrms.wfh_policy_types', []))
                ->only(['permanent', 'selected_days'])
                ->all(),
            'weekdays' => config('hrms.wfh_weekdays', []),
            'orgPolicy' => $this->service->resolveOrganizationPolicy($organization),
        ]);
    }

    public function store(StoreEmployeeWfhAssignmentRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->assign($employee, $data, $request->user());

        return redirect()
            ->route('hrms.wfh.assignments.index')
            ->with('status', 'hrms-wfh-assignment-created');
    }

    public function update(UpdateEmployeeWfhAssignmentRequest $request, EmployeeWfhAssignment $assignment): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->service->updateAssignment($assignment, $data, $request->user());

        return redirect()
            ->route('hrms.wfh.assignments.index')
            ->with('status', 'hrms-wfh-assignment-updated');
    }

    public function destroy(EmployeeWfhAssignment $assignment): RedirectResponse
    {
        $this->service->endAssignment($assignment, request()->user(), now(), __('Ended by HR'));

        return redirect()
            ->route('hrms.wfh.assignments.index')
            ->with('status', 'hrms-wfh-assignment-ended');
    }
}
