<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\EssAttendanceCorrectionRequest;
use App\Http\Requests\Ess\EssClockInRequest;
use App\Http\Requests\Ess\EssClockOutRequest;
use App\Http\Resources\Hrms\AttendanceResource;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Support\Api\ApiQuery;
use App\Support\Api\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceMeApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected HRMSApiFacadeService $facade,
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $summary = $this->facade->attendanceSummary($employee);

        return ApiResponse::success([
            'date' => $summary['date'] ?? null,
            'state' => $summary['state'] ?? null,
            'state_label' => $summary['state_label'] ?? null,
            'working_hours' => $summary['working_hours'] ?? null,
            'shift_info' => $summary['shift_info'] ?? null,
            'indicator' => $summary['indicator'] ?? null,
            'actions' => $summary['actions'] ?? null,
            'on_leave_today' => $summary['on_leave_today'] ?? false,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewAny', AttendanceRecord::class);

        $query = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->with('shift');

        ApiQuery::applyFilters($query, $request, [
            'status' => 'status',
        ]);

        if ($request->filled('from')) {
            $query->whereDate('attendance_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('attendance_date', '<=', $request->input('to'));
        }

        $paginator = $query->latest('attendance_date')
            ->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (AttendanceRecord $record) => (new AttendanceResource($record))->resolve(),
        );
    }

    public function calendar(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $calendar = $this->facade->attendanceCalendar();
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        [$year, $month] = array_values($calendar->normalizeYearMonth($year, $month));

        return ApiResponse::success($calendar->monthForEmployee($employee, $year, $month));
    }

    public function clockIn(EssClockInRequest $request): JsonResponse
    {
        $employee = $request->employee();
        $clockInAt = $request->filled('clock_in_at')
            ? Carbon::parse($request->validated('clock_in_at'))
            : null;

        $record = $this->facade->attendanceService()->clockIn(
            $employee,
            $clockInAt,
            $request->user(),
            'mobile',
            $request->verificationContext(),
        );

        return ApiResponse::success(
            new AttendanceResource($record->load('shift')),
            __('Checked in successfully.'),
        );
    }

    public function clockOut(EssClockOutRequest $request): JsonResponse
    {
        $employee = $request->employee();
        $clockOutAt = $request->filled('clock_out_at')
            ? Carbon::parse($request->validated('clock_out_at'))
            : null;

        $record = $this->facade->attendanceService()->clockOut(
            $employee,
            $clockOutAt,
            $request->user(),
            $request->verificationContext(),
        );

        return ApiResponse::success(
            new AttendanceResource($record->load('shift')),
            __('Checked out successfully.'),
        );
    }

    public function corrections(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());

        $items = AttendanceCorrection::query()
            ->where('employee_id', $employee->id)
            ->with('attendanceRecord')
            ->latest()
            ->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated($items, mapItem: function (AttendanceCorrection $correction) {
            return [
                'id' => $correction->id,
                'attendance_record_id' => $correction->attendance_record_id,
                'status' => $correction->status,
                'reason' => $correction->reason,
                'requested_clock_in_at' => $correction->requested_clock_in_at?->toIso8601String(),
                'requested_clock_out_at' => $correction->requested_clock_out_at?->toIso8601String(),
                'created_at' => $correction->created_at?->toIso8601String(),
            ];
        });
    }

    public function storeCorrection(EssAttendanceCorrectionRequest $request): JsonResponse
    {
        $record = AttendanceRecord::query()->findOrFail($request->validated('attendance_record_id'));
        $correction = $this->facade->attendanceService()->submitCorrection(
            $record,
            $request->validated(),
            $request->user(),
        );

        return ApiResponse::success($correction, __('Correction submitted.'), status: 201);
    }
}
