<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\ApproveAttendanceCorrectionRequest;
use App\Http\Requests\Hrms\AttendanceCorrectionRequest;
use App\Http\Requests\Hrms\ClockInRequest;
use App\Http\Requests\Hrms\ClockOutRequest;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\Hrms\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $date = $request->date('date') ?? now();

        return view('hrms.attendance.index', [
            'records' => AttendanceRecord::query()
                ->with(['employee', 'shift'])
                ->when($request->filled('date'), fn ($q) => $q->whereDate('attendance_date', $date))
                ->latest('attendance_date')
                ->paginate(15)
                ->withQueryString(),
            'employees' => Employee::query()->whereIn('status', config('hrms.clockable_employee_statuses', []))->orderBy('first_name')->get(),
            'filterDate' => $request->input('date'),
            'summary' => $this->service->dailySummary(Carbon::parse($date)),
        ]);
    }

    public function show(AttendanceRecord $attendance): View
    {
        $this->authorize('view', $attendance);
        $attendance->load(['employee', 'shift', 'corrections.reviewer']);

        return view('hrms.attendance.show', [
            'record' => $attendance,
        ]);
    }

    public function summary(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $date = Carbon::parse($request->input('date', now()->toDateString()));

        return view('hrms.attendance.summary', [
            'summary' => $this->service->dailySummary($date),
            'filterDate' => $date->toDateString(),
        ]);
    }

    public function clockIn(ClockInRequest $request): RedirectResponse
    {
        $clockInAt = $request->filled('clock_in_at') ? Carbon::parse($request->validated('clock_in_at')) : null;

        $this->service->clockIn($request->employee(), $clockInAt, $request->user());

        return redirect()->route('hrms.attendance.index')->with('status', 'hrms-attendance-clocked-in');
    }

    public function clockOut(ClockOutRequest $request): RedirectResponse
    {
        $clockOutAt = $request->filled('clock_out_at') ? Carbon::parse($request->validated('clock_out_at')) : null;

        $this->service->clockOut($request->employee(), $clockOutAt, $request->user());

        return redirect()->route('hrms.attendance.index')->with('status', 'hrms-attendance-clocked-out');
    }

    public function correctionsIndex(): View
    {
        $this->authorize('viewAny', AttendanceCorrection::class);

        return view('hrms.attendance.corrections.index', [
            'corrections' => AttendanceCorrection::query()
                ->with(['employee', 'attendanceRecord', 'reviewer'])
                ->latest()
                ->paginate(15),
            'records' => AttendanceRecord::query()->latest('attendance_date')->limit(50)->get(),
        ]);
    }

    public function storeCorrection(AttendanceCorrectionRequest $request): RedirectResponse
    {
        $this->service->submitCorrection(
            $request->attendanceRecord(),
            $request->validated(),
            $request->user(),
        );

        return redirect()->route('hrms.attendance.corrections.index')->with('status', 'hrms-attendance-correction-submitted');
    }

    public function approveCorrection(ApproveAttendanceCorrectionRequest $request, AttendanceCorrection $correction): RedirectResponse
    {
        $this->service->approveCorrection($correction, $request->validated(), $request->user());

        return redirect()->route('hrms.attendance.corrections.index')->with('status', 'hrms-attendance-correction-approved');
    }

    public function rejectCorrection(ApproveAttendanceCorrectionRequest $request, AttendanceCorrection $correction): RedirectResponse
    {
        $this->service->rejectCorrection($correction, $request->validated(), $request->user());

        return redirect()->route('hrms.attendance.corrections.index')->with('status', 'hrms-attendance-correction-rejected');
    }
}
