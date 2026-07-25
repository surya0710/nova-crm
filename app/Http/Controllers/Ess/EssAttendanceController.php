<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\EssAttendanceCorrectionRequest;
use App\Http\Requests\Ess\EssClockInRequest;
use App\Http\Requests\Ess\EssClockOutRequest;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\EssContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EssAttendanceController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected AttendanceService $service,
    ) {}

    public function index(): View
    {
        $employee = $this->essContext->requireEmployee();
        $this->authorize('viewAny', AttendanceRecord::class);

        $today = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', now())
            ->with('shift')
            ->first();

        return view('ess.attendance.index', [
            'employee' => $employee,
            'todayRecord' => $today,
            'records' => AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->with('shift')
                ->latest('attendance_date')
                ->paginate(15),
            'corrections' => AttendanceCorrection::query()
                ->where('employee_id', $employee->id)
                ->with('attendanceRecord')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function clockIn(EssClockInRequest $request): RedirectResponse
    {
        $employee = $request->employee();
        $clockInAt = $request->filled('clock_in_at') ? Carbon::parse($request->validated('clock_in_at')) : null;
        $this->service->clockIn($employee, $clockInAt, $request->user(), 'mobile');

        return redirect()->route('ess.attendance.index')->with('status', 'ess-attendance-clocked-in');
    }

    public function clockOut(EssClockOutRequest $request): RedirectResponse
    {
        $employee = $request->employee();
        $clockOutAt = $request->filled('clock_out_at') ? Carbon::parse($request->validated('clock_out_at')) : null;
        $this->service->clockOut($employee, $clockOutAt, $request->user());

        return redirect()->route('ess.attendance.index')->with('status', 'ess-attendance-clocked-out');
    }

    public function storeCorrection(EssAttendanceCorrectionRequest $request): RedirectResponse
    {
        $record = AttendanceRecord::query()->findOrFail($request->validated('attendance_record_id'));
        $this->service->submitCorrection($record, $request->validated(), $request->user());

        return redirect()->route('ess.attendance.index')->with('status', 'ess-attendance-correction-submitted');
    }
}
