<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateEmployeeRequest;
use App\Http\Requests\Hrms\ExitEmployeeRequest;
use App\Http\Requests\Hrms\LinkEmployeeUserRequest;
use App\Http\Requests\Hrms\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Services\Hrms\EmployeeProvisioningService;
use App\Services\Hrms\EmployeeService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $service,
        protected EmployeeProvisioningService $provisioning,
        protected TenantContext $tenantContext,
    ) {
        $this->authorizeResource(Employee::class, 'employee');
    }

    public function index(): View
    {
        return view('hrms.employees.index', [
            'employees' => Employee::query()
                ->with(['branch', 'department', 'designation', 'reportingManager', 'user'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $organization = $this->tenantContext->get();

        return view('hrms.employees.create', [
            'employee' => new Employee,
            'branches' => Branch::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'designations' => Designation::query()->orderBy('name')->get(),
            'managers' => Employee::query()->orderBy('first_name')->get(),
            'organizationUsers' => $organization?->users()->orderBy('users.name')->get() ?? collect(),
        ]);
    }

    public function store(CreateEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('create_user') || ! empty($data['user_email'] ?? null)) {
            $employee = $this->provisioning->provision([
                ...$data,
                'create_user' => true,
                'entry_point' => 'hrms',
                'user' => [
                    'name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
                    'email' => $data['user_email'] ?? $data['email'] ?? null,
                    'role' => $data['role'] ?? 'employee',
                ],
                'role' => $data['role'] ?? 'employee',
            ], $request->user());
        } else {
            $employee = $this->service->createEmployee($data, $request->user());
        }

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-employee-created');
    }

    public function show(Employee $employee): View
    {
        $organization = $this->tenantContext->get();
        $employee->load([
            'branch',
            'department',
            'designation',
            'reportingManager',
            'user',
            'emergencyContacts',
            'bankAccounts',
            'identities',
            'educations',
            'experiences',
            'documents' => fn ($query) => $query->latest()->limit(5),
            'assets' => fn ($query) => $query->latest()->limit(5),
            'leaveApplications' => fn ($query) => $query->with('leaveType')->latest()->limit(5),
            'attendanceRecords' => fn ($query) => $query->latest('attendance_date')->limit(5),
        ]);

        return view('hrms.employees.show', [
            'employee' => $employee,
            'organizationUsers' => $organization?->users()->orderBy('users.name')->get() ?? collect(),
            'documentCount' => $employee->documents()->count(),
            'assetCount' => $employee->assets()->count(),
        ]);
    }

    public function edit(Employee $employee): View
    {
        $organization = $this->tenantContext->get();
        $employee->load(['emergencyContacts', 'bankAccounts', 'identities', 'educations', 'experiences']);

        return view('hrms.employees.edit', [
            'employee' => $employee,
            'branches' => Branch::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'designations' => Designation::query()->orderBy('name')->get(),
            'managers' => Employee::query()->where('id', '!=', $employee->id)->orderBy('first_name')->get(),
            'organizationUsers' => $organization?->users()->orderBy('users.name')->get() ?? collect(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->service->updateEmployee($employee, $request->validated(), $request->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-employee-updated');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);
        $employee->delete();

        return redirect()->route('hrms.employees.index')->with('status', 'hrms-employee-deleted');
    }

    public function exit(ExitEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->service->exitEmployee($employee, $request->validated(), $request->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-employee-exited');
    }

    public function linkUser(LinkEmployeeUserRequest $request, Employee $employee): RedirectResponse
    {
        if ($request->boolean('create_user')) {
            $this->service->createAndLinkUser($employee, $request->validated(), $request->user());
        } else {
            $user = User::query()->findOrFail((int) $request->validated('user_id'));
            $this->service->linkUser($employee, $user, $request->user());
        }

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-employee-user-linked');
    }

    public function unlinkUser(Employee $employee): RedirectResponse
    {
        $this->authorize('manage', $employee);
        $this->service->unlinkUser($employee, request()->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-employee-user-unlinked');
    }
}
