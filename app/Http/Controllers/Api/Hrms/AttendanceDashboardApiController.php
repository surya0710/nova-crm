<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\EssClockInRequest;
use App\Http\Requests\Ess\EssClockOutRequest;
use App\Services\Hrms\AttendanceDashboardService;
use App\Services\Hrms\AttendanceService;
use App\Services\Hrms\EssContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceDashboardApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected AttendanceDashboardService $dashboard,
        protected AttendanceService $attendance,
    ) {}

    public function employeeDashboard(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('ess.access'), 403);

        $employee = $this->essContext->requireEmployee($request->user());
        $summary = $this->dashboard->employeeSummary($employee);

        return response()->json(['data' => $this->serializeEmployeeSummary($summary)]);
    }

    public function teamSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('manager.dashboard'), 403);

        $manager = $this->essContext->requireEmployee($request->user());

        return response()->json([
            'data' => $this->dashboard->teamSummary($manager),
        ]);
    }

    public function checkIn(EssClockInRequest $request): JsonResponse
    {
        $employee = $request->employee();
        $clockInAt = $request->filled('clock_in_at') ? Carbon::parse($request->validated('clock_in_at')) : null;
        $record = $this->attendance->clockIn($employee, $clockInAt, $request->user(), 'api');
        $summary = $this->dashboard->employeeSummary($employee);

        return response()->json([
            'message' => __('Checked in successfully.'),
            'record' => $record,
            'dashboard' => $this->serializeEmployeeSummary($summary),
        ]);
    }

    public function checkOut(EssClockOutRequest $request): JsonResponse
    {
        $employee = $request->employee();
        $clockOutAt = $request->filled('clock_out_at') ? Carbon::parse($request->validated('clock_out_at')) : null;
        $record = $this->attendance->clockOut($employee, $clockOutAt, $request->user());
        $summary = $this->dashboard->employeeSummary($employee);

        return response()->json([
            'message' => __('Checked out successfully.'),
            'record' => $record,
            'dashboard' => $this->serializeEmployeeSummary($summary),
        ]);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    protected function serializeEmployeeSummary(array $summary): array
    {
        return [
            'date' => $summary['date'],
            'state' => $summary['state'],
            'state_label' => $summary['state_label'],
            'working_hours' => $summary['working_hours'],
            'shift_info' => $summary['shift_info'],
            'indicator' => $summary['indicator'],
            'actions' => $summary['actions'],
            'on_leave_today' => $summary['on_leave_today'],
        ];
    }
}
