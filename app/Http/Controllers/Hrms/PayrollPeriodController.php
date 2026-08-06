<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreatePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use App\Services\Hrms\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function __construct(protected PayrollService $service)
    {
        $this->authorizeResource(PayrollPeriod::class, 'period');
    }

    public function index(): View
    {
        return view('hrms.payroll.periods.index', [
            'periods' => PayrollPeriod::query()->latest('start_date')->paginate(20),
            'statuses' => config('hrms.payroll_period_statuses', []),
        ]);
    }

    public function store(CreatePayrollPeriodRequest $request): RedirectResponse
    {
        $this->service->createPayrollPeriod($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.periods.index')
            ->with('status', 'hrms-payroll-period-created');
    }

    public function lock(Request $request, PayrollPeriod $period): RedirectResponse
    {
        $this->authorize('lock', $period);
        $this->service->lockPayrollPeriod($period, $request->user());

        return redirect()->route('hrms.payroll.periods.index')
            ->with('status', 'hrms-payroll-period-locked');
    }
}
