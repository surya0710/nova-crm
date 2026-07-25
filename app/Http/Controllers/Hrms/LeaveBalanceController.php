<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\AdjustLeaveBalanceRequest;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveType;
use App\Services\Hrms\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function __construct(protected LeaveService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveApplication::class);

        $year = (int) $request->input('year', now()->year);

        $query = LeaveBalance::query()
            ->with(['employee', 'leaveType'])
            ->where('year', $year)
            ->latest();

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        return view('hrms.leave-balances.index', [
            'balances' => $query->paginate(15)->withQueryString(),
            'employees' => Employee::query()->orderBy('first_name')->get(),
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
            'year' => $year,
            'filterEmployeeId' => $request->input('employee_id'),
        ]);
    }

    public function adjust(AdjustLeaveBalanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employee = Employee::query()->findOrFail($data['employee_id']);
        $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);

        $balance = $this->service->findOrCreateBalance($employee, $leaveType, (int) $data['year']);
        $this->service->adjustBalance($balance, (float) $data['quantity'], $data['remarks'], $request->user());

        return redirect()->route('hrms.leave-balances.index', ['year' => $data['year']])
            ->with('status', 'hrms-leave-balance-adjusted');
    }

    public function allocate(Request $request): RedirectResponse
    {
        $this->authorize('create', LeaveApplication::class);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'days' => ['required', 'numeric', 'min:0.5'],
        ]);

        $employee = Employee::query()->findOrFail($validated['employee_id']);
        $leaveType = LeaveType::query()->findOrFail($validated['leave_type_id']);

        $this->service->allocateBalance(
            $employee,
            $leaveType,
            (int) $validated['year'],
            (float) $validated['days'],
            $request->user(),
        );

        return redirect()->route('hrms.leave-balances.index', ['year' => $validated['year']])
            ->with('status', 'hrms-leave-balance-allocated');
    }

    public function ledger(Request $request): View
    {
        $this->authorize('viewAny', LeaveApplication::class);

        $query = LeaveBalanceTransaction::query()
            ->with(['leaveBalance.employee', 'leaveBalance.leaveType'])
            ->latest();

        if ($request->filled('leave_balance_id')) {
            $query->where('leave_balance_id', $request->integer('leave_balance_id'));
        }

        return view('hrms.leave-balances.ledger', [
            'transactions' => $query->paginate(20)->withQueryString(),
            'transactionTypes' => config('hrms.leave_balance_transaction_types', []),
        ]);
    }
}
