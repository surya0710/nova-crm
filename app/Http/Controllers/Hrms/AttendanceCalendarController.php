<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\Hrms\AttendanceCalendarService;
use App\Services\Hrms\EssContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        [$year, $month] = array_values($this->calendar->normalizeYearMonth($year, $month));
        $view = $request->input('view', 'my');
        $employeeId = $request->integer('employee_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;

        $canViewTeam = $request->user()?->hasPermission('manager.dashboard') ?? false;
        $canFilterEmployees = $request->user()?->hasPermission('attendance.view') ?? false;
        $manager = $this->essContext->employeeFor($request->user());
        $navigation = $this->calendar->navigationConfig();

        $employees = $this->resolveFilterEmployees($canFilterEmployees, $canViewTeam, $manager);
        $departments = $canFilterEmployees ? $this->calendar->filterableDepartments() : collect();

        $employee = $this->resolveEmployee($request, $employeeId, $employees, $canFilterEmployees, $canViewTeam, $manager, $departmentId);

        if ($view === 'team' && $canViewTeam) {
            abort_unless($manager !== null, 403);

            return view('hrms.attendance.calendar', [
                'mode' => 'team',
                'calendar' => $this->calendar->teamMonthForManager($manager, $year, $month),
                'year' => $year,
                'month' => $month,
                'view' => $view,
                'employee' => $employee,
                'employees' => $employees,
                'departments' => $departments,
                'departmentId' => $departmentId,
                'canViewTeam' => $canViewTeam,
                'canFilterEmployees' => $canFilterEmployees,
                'navigation' => $navigation,
                'apiUrl' => url('/api/v1/attendance/calendar'),
            ]);
        }

        return view('hrms.attendance.calendar', [
            'mode' => 'employee',
            'calendar' => $this->calendar->monthForEmployee($employee, $year, $month),
            'year' => $year,
            'month' => $month,
            'view' => $view,
            'employee' => $employee,
            'employees' => $employees,
            'departments' => $departments,
            'departmentId' => $departmentId,
            'canViewTeam' => $canViewTeam,
            'canFilterEmployees' => $canFilterEmployees,
            'navigation' => $navigation,
            'apiUrl' => url('/api/v1/attendance/calendar'),
        ]);
    }

    /**
     * @return Collection<int, Employee>
     */
    protected function resolveFilterEmployees(
        bool $canFilterEmployees,
        bool $canViewTeam,
        ?Employee $manager,
    ): Collection {
        if ($canFilterEmployees) {
            return $this->calendar->filterableEmployees();
        }

        if ($canViewTeam && $manager !== null) {
            return $this->calendar->teamEmployeesForManager($manager);
        }

        return collect();
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    protected function resolveEmployee(
        Request $request,
        ?int $employeeId,
        Collection $employees,
        bool $canFilterEmployees,
        bool $canViewTeam,
        ?Employee $manager,
        ?int $departmentId = null,
    ): Employee {
        $scoped = $departmentId !== null
            ? $employees->where('department_id', $departmentId)->values()
            : $employees;

        if ($employeeId !== null) {
            if ($canFilterEmployees) {
                return Employee::query()->findOrFail($employeeId);
            }

            if ($canViewTeam && $manager !== null) {
                $report = $employees->firstWhere('id', $employeeId)
                    ?? Employee::query()
                        ->where('id', $employeeId)
                        ->where('reporting_manager_id', $manager->id)
                        ->first();

                abort_unless($report !== null, 403);

                return $report;
            }
        }

        $self = $this->essContext->employeeFor($request->user());
        if ($self !== null && ($departmentId === null || (int) $self->department_id === $departmentId)) {
            return $self;
        }

        if ($scoped->isNotEmpty()) {
            return $scoped->first();
        }

        if ($employees->isNotEmpty()) {
            return $employees->first();
        }

        if ($canFilterEmployees) {
            return Employee::query()
                ->whereIn('status', config('hrms.clockable_employee_statuses', []))
                ->orderBy('first_name')
                ->firstOrFail();
        }

        abort(403, __('Employee profile required to view attendance calendar.'));
    }
}
