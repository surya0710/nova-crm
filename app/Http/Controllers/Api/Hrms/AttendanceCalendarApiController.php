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

        $this->authorize('viewAny', AttendanceRecord::class);

        if ($request->boolean('team') && $request->user()?->hasPermission('manager.dashboard')) {
            $manager = $this->essContext->requireEmployee($request->user());

            return response()->json([
                'data' => $this->calendar->appendNavigation(
                    $this->calendar->teamMonthForManager($manager, $year, $month)
                ),
            ]);
        }

        $employee = $this->resolveEmployee($request, $employeeId);

        return response()->json([
            'data' => $this->calendar->appendNavigation(
                $this->calendar->monthForEmployee($employee, $year, $month)
            ),
        ]);
    }

    protected function resolveEmployee(Request $request, ?int $employeeId): Employee
    {
        if ($employeeId === null) {
            return $this->essContext->requireEmployee($request->user());
        }

        if ($request->user()?->hasPermission('attendance.view')) {
            return Employee::query()->findOrFail($employeeId);
        }

        if ($request->user()?->hasPermission('manager.dashboard')) {
            $manager = $this->essContext->requireEmployee($request->user());
            $report = Employee::query()
                ->where('id', $employeeId)
                ->where('reporting_manager_id', $manager->id)
                ->first();

            abort_unless($report !== null, 403);

            return $report;
        }

        $self = $this->essContext->requireEmployee($request->user());
        abort_unless($self->id === $employeeId, 403);

        return $self;
    }
}
