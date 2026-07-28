<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\Hrms\AttendanceCalendarService;
use App\Services\Hrms\EssContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceCalendarController extends Controller
{
    public function __construct(
        protected AttendanceCalendarService $calendar,
        protected EssContext $essContext,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $view = $request->input('view', 'my');
        $employeeId = $request->integer('employee_id') ?: null;

        $employee = $this->resolveEmployee($request, $employeeId);
        $canViewTeam = $request->user()?->hasPermission('manager.dashboard') ?? false;
        $canFilterEmployees = $request->user()?->hasPermission('attendance.view') ?? false;

        if ($view === 'team' && $canViewTeam) {
            $manager = $this->essContext->employeeFor($request->user());
            abort_unless($manager !== null, 403);

            return view('hrms.attendance.calendar', [
                'mode' => 'team',
                'calendar' => $this->calendar->teamMonthForManager($manager, $year, $month),
                'year' => $year,
                'month' => $month,
                'view' => $view,
                'employee' => $employee,
                'employees' => $canFilterEmployees
                    ? Employee::query()->whereIn('status', config('hrms.clockable_employee_statuses', []))->orderBy('first_name')->get()
                    : collect(),
                'canViewTeam' => $canViewTeam,
                'canFilterEmployees' => $canFilterEmployees,
            ]);
        }

        return view('hrms.attendance.calendar', [
            'mode' => 'employee',
            'calendar' => $this->calendar->monthForEmployee($employee, $year, $month),
            'year' => $year,
            'month' => $month,
            'view' => 'my',
            'employee' => $employee,
            'employees' => $canFilterEmployees
                ? Employee::query()->whereIn('status', config('hrms.clockable_employee_statuses', []))->orderBy('first_name')->get()
                : collect(),
            'canViewTeam' => $canViewTeam,
            'canFilterEmployees' => $canFilterEmployees,
        ]);
    }

    protected function resolveEmployee(Request $request, ?int $employeeId): Employee
    {
        if ($employeeId !== null && $request->user()?->hasPermission('attendance.view')) {
            return Employee::query()->findOrFail($employeeId);
        }

        $employee = $this->essContext->employeeFor($request->user());
        if ($employee !== null) {
            return $employee;
        }

        if ($request->user()?->hasPermission('attendance.view')) {
            return Employee::query()
                ->whereIn('status', config('hrms.clockable_employee_statuses', []))
                ->orderBy('first_name')
                ->firstOrFail();
        }

        abort(403, __('Employee profile required to view attendance calendar.'));
    }
}
