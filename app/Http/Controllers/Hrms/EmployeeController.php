<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\BulkProvisionEmployeesRequest;
use App\Http\Requests\Hrms\CreateEmployeeRequest;
use App\Http\Requests\Hrms\ExitEmployeeRequest;
use App\Http\Requests\Hrms\LinkEmployeeUserRequest;
use App\Http\Requests\Hrms\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Services\Bulk\BulkOperationsService;
use App\Services\Hrms\AttendanceCalendarService;
use App\Services\Hrms\EmployeeProvisioningService;
use App\Services\Hrms\EmployeeProfileService;
use App\Services\Hrms\EmployeeService;
use App\Services\Hrms\LeaveService;
use App\Services\Identity\BulkEmployeeUserProvisioningService;
use App\Services\Identity\UserAccountService;
use App\Services\Identity\UserInvitationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $service,
        protected EmployeeProvisioningService $provisioning,
        protected TenantContext $tenantContext,
        protected UserInvitationService $invitations,
        protected UserAccountService $accounts,
        protected BulkEmployeeUserProvisioningService $bulkProvisioning,
        protected BulkOperationsService $bulkOperations,
        protected AttendanceCalendarService $attendanceCalendar,
        protected LeaveService $leaveService,
        protected EmployeeProfileService $profileService,
    ) {
        $this->authorizeResource(Employee::class, 'employee');
    }

    public function index(): View
    {
        $organization = $this->requireOrganization();
        $employees = Employee::query()
            ->with(['branch', 'department', 'designation', 'reportingManager', 'user.latestInvitation'])
            ->latest()
            ->paginate(15);

        return view('hrms.employees.index', [
            'employees' => $employees,
            'bulkActions' => $this->bulkOperations->availableActionsFor(
                request()->user(),
                $organization,
                'employee'
            ),
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
                    'role' => $data['role'] ?? config('identity.default_employee_role', 'employee'),
                ],
                'role' => $data['role'] ?? config('identity.default_employee_role', 'employee'),
                'send_invitation' => $request->boolean('send_invitation', true),
                'portal_access' => $request->boolean('portal_access', true),
                'notify' => $request->boolean('send_invitation', true),
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
            'reportingManager.designation',
            'user.latestInvitation',
            'emergencyContacts',
            'bankAccounts',
            'identities',
            'educations',
            'experiences',
            'skills',
            'certifications',
            'directReports.designation',
            'documents' => fn ($query) => $query->latest()->limit(5),
            'assets' => fn ($query) => $query->latest()->limit(5),
            'leaveApplications' => fn ($query) => $query->with('leaveType')->latest()->limit(5),
            'attendanceRecords' => fn ($query) => $query->latest('attendance_date')->limit(5),
        ]);

        $loginActivity = $employee->user
            ? $this->accounts->loginActivity($employee->user)
            : null;

        $invitationStatus = ($employee->user && $organization)
            ? $this->invitations->invitationStatus($employee->user, $organization)
            : null;

        $profile = $this->profileService->profileDashboard($employee);
        $attendanceSummary = $profile['attendance']['raw'];
        $leaveBalances = $profile['leave']['balances'];
        $currentProjects = $profile['work']['projects'];

        return view('hrms.employees.show', [
            'employee' => $employee,
            'organizationUsers' => $organization?->users()->orderBy('users.name')->get() ?? collect(),
            'documentCount' => $employee->documents()->count(),
            'assetCount' => $employee->assets()->count(),
            'loginActivity' => $loginActivity,
            'invitationStatus' => $invitationStatus,
            'attendanceSummary' => $attendanceSummary,
            'attendancePercentage' => $profile['attendance']['attendance_percentage'],
            'leaveBalances' => $leaveBalances,
            'leaveSummary' => $profile['leave'],
            'currentProjects' => $currentProjects,
            'workSummary' => $profile['work'],
            'reportingStructure' => $profile['reporting'],
            'profileCompletion' => $profile['completion'],
            'upcomingHolidays' => $profile['upcoming_holidays'],
        ]);
    }

    public function edit(Employee $employee): View
    {
        $organization = $this->tenantContext->get();
        $employee->load([
            'emergencyContacts',
            'bankAccounts',
            'identities',
            'educations',
            'experiences',
            'skills',
            'certifications',
        ]);

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

    public function resendInvitation(Employee $employee): RedirectResponse
    {
        $this->authorize('manage', $employee);
        $organization = $this->requireOrganization();
        $user = $this->requireLinkedUser($employee);

        $this->invitations->resend($user, $organization, request()->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-invitation-resent');
    }

    public function enablePortal(Employee $employee): RedirectResponse
    {
        $this->authorize('manage', $employee);
        $organization = $this->requireOrganization();
        $user = $this->requireLinkedUser($employee);

        $this->accounts->enablePortal($user, $organization, request()->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-portal-enabled');
    }

    public function disablePortal(Employee $employee): RedirectResponse
    {
        $this->authorize('manage', $employee);
        $organization = $this->requireOrganization();
        $user = $this->requireLinkedUser($employee);

        $this->accounts->disablePortal($user, $organization, request()->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-portal-disabled');
    }

    public function lockAccount(Employee $employee): RedirectResponse
    {
        $this->authorize('manage', $employee);
        $organization = $this->requireOrganization();
        $user = $this->requireLinkedUser($employee);

        $this->accounts->lock($user, $organization, request()->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-account-locked');
    }

    public function unlockAccount(Employee $employee): RedirectResponse
    {
        $this->authorize('manage', $employee);
        $organization = $this->requireOrganization();
        $user = $this->requireLinkedUser($employee);

        $this->accounts->unlock($user, $organization, request()->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-account-unlocked');
    }

    public function resetPassword(Employee $employee): RedirectResponse
    {
        $this->authorize('manage', $employee);
        $organization = $this->requireOrganization();
        $user = $this->requireLinkedUser($employee);

        $this->accounts->sendPasswordReset($user, $organization, request()->user());

        return redirect()->route('hrms.employees.show', $employee)->with('status', 'hrms-password-reset-sent');
    }

    public function bulkProvision(BulkProvisionEmployeesRequest $request): RedirectResponse
    {
        $organization = $this->requireOrganization();
        $batch = $this->bulkProvisioning->start(
            $organization,
            $request->user(),
            $request->validated('employee_ids'),
            [
                'role' => $request->validated('role') ?? config('identity.default_employee_role', 'employee'),
                'send_invitation' => $request->boolean('send_invitation', true),
                'portal_access' => $request->boolean('portal_access', true),
            ]
        );

        return redirect()
            ->route('hrms.employees.index')
            ->with('status', __('Login account generation started (:total employees).', ['total' => $batch->total]));
    }

    protected function requireOrganization()
    {
        $organization = $this->tenantContext->get();
        abort_unless($organization, 404);

        return $organization;
    }

    protected function requireLinkedUser(Employee $employee): User
    {
        abort_unless($employee->user_id, 422);
        $user = $employee->user ?? User::query()->find($employee->user_id);
        abort_unless($user, 404);

        return $user;
    }
}
