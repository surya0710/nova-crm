<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateLeaveTypeRequest;
use App\Http\Requests\Hrms\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use App\Services\Hrms\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function __construct(protected LeaveService $service)
    {
        $this->authorizeResource(LeaveType::class, 'leave_type');
    }

    public function index(): View
    {
        return view('hrms.leave-types.index', [
            'leaveTypes' => LeaveType::query()->latest()->paginate(15),
        ]);
    }

    public function store(CreateLeaveTypeRequest $request): RedirectResponse
    {
        $this->service->createLeaveType($request->validated(), $request->user());

        return redirect()->route('hrms.leave-types.index')->with('status', 'hrms-leave-type-created');
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $this->service->updateLeaveType($leaveType, $request->validated(), $request->user());

        return redirect()->route('hrms.leave-types.index')->with('status', 'hrms-leave-type-updated');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $this->service->deleteLeaveType($leaveType, request()->user());

        return redirect()->route('hrms.leave-types.index')->with('status', 'hrms-leave-type-deleted');
    }
}
