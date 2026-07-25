<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\EssApplyLeaveRequest;
use App\Models\LeaveApplication;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveType;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EssLeaveController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected LeaveService $leaveService,
    ) {}

    public function index(): View
    {
        $employee = $this->essContext->requireEmployee();
        $this->authorize('viewAny', LeaveApplication::class);

        $balances = $this->leaveService->getBalancesForEmployee($employee);
        $balanceIds = $balances->pluck('id');

        return view('ess.leave.index', [
            'employee' => $employee,
            'balances' => $balances,
            'applications' => LeaveApplication::query()
                ->where('employee_id', $employee->id)
                ->with('leaveType')
                ->latest('submitted_at')
                ->paginate(15),
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
            'transactions' => LeaveBalanceTransaction::query()
                ->whereIn('leave_balance_id', $balanceIds)
                ->with('leaveBalance.leaveType')
                ->latest()
                ->limit(20)
                ->get(),
            'statuses' => config('hrms.leave_statuses', []),
        ]);
    }

    public function store(EssApplyLeaveRequest $request): RedirectResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->leaveService->applyLeave($employee, $request->validated(), $request->user(), true);

        return redirect()->route('ess.leave.index')->with('status', 'ess-leave-applied');
    }

    public function destroy(LeaveApplication $application): RedirectResponse
    {
        $employee = $this->essContext->requireEmployee();
        abort_unless($application->employee_id === $employee->id, 404);
        $this->authorize('withdrawOwn', $application);
        $this->leaveService->withdrawLeave($application, request()->user());

        return redirect()->route('ess.leave.index')->with('status', 'ess-leave-withdrawn');
    }
}
