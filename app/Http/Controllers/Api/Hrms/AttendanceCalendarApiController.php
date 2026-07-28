<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\Hrms\AttendanceCalendarService;
use App\Services\Hrms\EssContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCalendarApiController extends Controller
{
    public function __construct(
        protected AttendanceCalendarService $calendar,
        protected EssContext $essContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('ess.access') || $request->user()?->hasPermission('attendance.view'),
            403,
        );

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        [$year, $month] = array_values($this->calendar->normalizeYearMonth($year, $month));
        $employeeId = $request->integer('employee_id') ?: null;

        if ($employeeId !== null && $request->user()?->hasPermission('attendance.view')) {
            $employee = Employee::query()->findOrFail($employeeId);
        } else {
            $employee = $this->essContext->requireEmployee($request->user());
        }

        $this->authorize('viewAny', AttendanceRecord::class);

        $data = $this->calendar->monthForEmployee($employee, $year, $month);

        if ($request->boolean('team') && $request->user()?->hasPermission('manager.dashboard')) {
            $manager = $this->essContext->requireEmployee($request->user());

            return response()->json([
                'data' => $this->calendar->appendNavigation(
                    $this->calendar->teamMonthForManager($manager, $year, $month)
                ),
            ]);
        }

        return response()->json([
            'data' => $this->calendar->appendNavigation($data),
        ]);
    }
}
