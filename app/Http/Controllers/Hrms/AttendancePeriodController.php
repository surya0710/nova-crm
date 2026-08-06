<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\StoreAttendancePeriodRequest;
use App\Models\AttendancePeriod;
use App\Models\PayrollPeriod;
use App\Services\Hrms\AttendanceLockService;
use App\Services\Hrms\AttendanceValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendancePeriodController extends Controller
{
    public function __construct(
        protected AttendanceLockService $lockService,
        protected AttendanceValidationService $validationService,
    ) {}

    public function index(Request $request): View
    {
        $periods = AttendancePeriod::query()
            ->with(['payrollPeriod', 'lockedBy', 'frozenBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('hrms.attendance.periods.index', [
            'periods' => $periods,
            'statuses' => config('hrms.attendance_period_statuses', []),
        ]);
    }

    public function create(): View
    {
        return view('hrms.attendance.periods.form', [
            'period' => null,
            'payrollPeriods' => PayrollPeriod::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function store(StoreAttendancePeriodRequest $request): RedirectResponse
    {
        $data = $request->periodData();
        $data['organization_id'] = app(\App\Services\TenantContext::class)->id();

        $this->lockService->createPeriod($data, $request->user());

        return redirect()
            ->route('hrms.attendance.periods.index')
            ->with('status', __('attendance.periods.created'));
    }

    public function show(AttendancePeriod $period): View
    {
        $validation = $this->validationService->validatePeriod($period);

        return view('hrms.attendance.periods.show', [
            'period' => $period->load(['snapshots', 'payrollPeriod', 'lockedBy', 'frozenBy', 'reopenedBy']),
            'validation' => $validation,
            'activeSnapshot' => $period->activeSnapshot(),
        ]);
    }

    public function freeze(Request $request, AttendancePeriod $period): RedirectResponse
    {
        $this->lockService->freeze($period, $request->user());

        return redirect()
            ->route('hrms.attendance.periods.show', $period)
            ->with('status', __('attendance.periods.frozen'));
    }

    public function lock(Request $request, AttendancePeriod $period): RedirectResponse
    {
        $this->lockService->lock($period, $request->user());

        return redirect()
            ->route('hrms.attendance.periods.show', $period)
            ->with('status', __('attendance.periods.locked'));
    }

    public function reopen(Request $request, AttendancePeriod $period): RedirectResponse
    {
        $this->lockService->reopen($period, $request->user());

        return redirect()
            ->route('hrms.attendance.periods.show', $period)
            ->with('status', __('attendance.periods.reopened'));
    }

    public function validatePeriod(AttendancePeriod $period): View
    {
        return view('hrms.attendance.periods.validation', [
            'period' => $period,
            'validation' => $this->validationService->validatePeriod($period),
        ]);
    }
}
