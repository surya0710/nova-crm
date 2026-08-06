<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\StorePayrollAdjustmentRequest;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Services\Hrms\PayrollAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollAdjustmentController extends Controller
{
    public function __construct(protected PayrollAdjustmentService $service)
    {
        $this->authorizeResource(PayrollAdjustment::class, 'adjustment');
    }

    public function index(): View
    {
        return view('hrms.payroll.adjustments.index', [
            'adjustments' => PayrollAdjustment::query()
                ->with(['employee', 'payrollPeriod', 'createdBy'])
                ->latest()
                ->paginate(20),
            'employees' => Employee::query()->orderBy('first_name')->limit(500)->get(),
            'periods' => PayrollPeriod::query()->orderByDesc('start_date')->limit(24)->get(),
            'types' => config('hrms.payroll.adjustment_types', []),
        ]);
    }

    public function store(StorePayrollAdjustmentRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.adjustments.index')
            ->with('status', 'hrms-payroll-adjustment-created');
    }

    public function approve(Request $request, PayrollAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('approve', $adjustment);
        $this->service->approve($adjustment, $request->user());

        return redirect()->route('hrms.payroll.adjustments.index')
            ->with('status', 'hrms-payroll-adjustment-approved');
    }

    public function reject(Request $request, PayrollAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('approve', $adjustment);
        $this->service->reject($adjustment, $request->user(), $request->input('reason'));

        return redirect()->route('hrms.payroll.adjustments.index')
            ->with('status', 'hrms-payroll-adjustment-rejected');
    }
}
