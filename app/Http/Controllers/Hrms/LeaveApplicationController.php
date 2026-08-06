<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\ApplyLeaveRequest;
use App\Http\Requests\Hrms\ApproveLeaveRequest;
use App\Http\Requests\Hrms\CancelLeaveRequest;
use App\Http\Requests\Hrms\RejectLeaveRequest;
use App\Http\Requests\Hrms\UpdateLeaveApplicationRequest;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Services\Hrms\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveApplicationController extends Controller
{
    public function __construct(protected LeaveService $service)
    {
        $this->authorizeResource(LeaveApplication::class, 'leave_application');
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveApplication::class);

        $query = LeaveApplication::query()
            ->with(['employee', 'leaveType'])
            ->latest('submitted_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('hrms.leave-applications.index', [
            'applications' => $query->paginate(15)->withQueryString(),
            'employees' => Employee::query()->orderBy('first_name')->get(),
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => config('hrms.leave_statuses', []),
            'filterStatus' => $request->string('status')->toString(),
        ]);
    }

    public function show(LeaveApplication $leaveApplication): View
    {
        return view('hrms.leave-applications.show', [
            'application' => $leaveApplication->load(['employee', 'leaveType', 'approvalSteps.approverEmployee', 'approvalSteps.approverUser']),
            'statuses' => config('hrms.leave_statuses', []),
            'stepStatuses' => config('hrms.leave_approval_step_statuses', []),
        ]);
    }

    public function store(ApplyLeaveRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        $this->service->applyLeave(
            $employee,
            $request->validated(),
            $request->user(),
            (bool) $request->boolean('submit', true),
        );

        return redirect()->route('hrms.leave-applications.index')->with('status', 'hrms-leave-applied');
    }

    public function update(UpdateLeaveApplicationRequest $request, LeaveApplication $leaveApplication): RedirectResponse
    {
        $this->service->updateLeave($leaveApplication, $request->validated(), $request->user());

        return redirect()->route('hrms.leave-applications.show', $leaveApplication)->with('status', 'hrms-leave-updated');
    }

    public function destroy(LeaveApplication $leaveApplication): RedirectResponse
    {
        $this->service->withdrawLeave($leaveApplication, request()->user());

        return redirect()->route('hrms.leave-applications.index')->with('status', 'hrms-leave-withdrawn');
    }

    public function approve(ApproveLeaveRequest $request, LeaveApplication $leaveApplication): RedirectResponse
    {
        $this->service->approveLeave($leaveApplication, $request->user(), $request->validated('remarks'));

        return redirect()->route('hrms.leave-applications.show', $leaveApplication)->with('status', 'hrms-leave-approved');
    }

    public function reject(RejectLeaveRequest $request, LeaveApplication $leaveApplication): RedirectResponse
    {
        $this->service->rejectLeave($leaveApplication, $request->user(), $request->validated('remarks'));

        return redirect()->route('hrms.leave-applications.show', $leaveApplication)->with('status', 'hrms-leave-rejected');
    }

    public function cancel(CancelLeaveRequest $request, LeaveApplication $leaveApplication): RedirectResponse
    {
        $this->service->cancelLeave($leaveApplication, $request->user(), $request->validated('remarks'));

        return redirect()->route('hrms.leave-applications.show', $leaveApplication)->with('status', 'hrms-leave-cancelled');
    }

    public function approvalQueue(): View
    {
        $this->authorize('viewAny', LeaveApplication::class);

        $applications = LeaveApplication::query()
            ->where('status', 'pending')
            ->with(['employee', 'leaveType', 'approvalSteps'])
            ->latest('submitted_at')
            ->paginate(15);

        return view('hrms.leave-applications.approval-queue', [
            'applications' => $applications,
        ]);
    }
}
