<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\ProjectMember;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Services\WorkloadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class EmployeeProfileService
{
    public function __construct(
        protected AttendanceCalendarService $attendanceCalendar,
        protected LeaveService $leaveService,
        protected WorkloadService $workloadService,
    ) {}

    /**
     * @return array{
     *     percentage: int,
     *     sections: list<array{key: string, label: string, complete: bool, weight: int}>
     * }
     */
    public function profileCompletion(Employee $employee): array
    {
        $employee->loadMissing([
            'emergencyContacts',
            'skills',
            'educations',
            'experiences',
            'certifications',
            'identities',
        ]);

        $checks = [
            'personal' => filled($employee->first_name)
                && filled($employee->email ?? $employee->personal_email)
                && filled($employee->mobile ?? $employee->phone)
                && filled($employee->date_of_birth),
            'emergency_contact' => $employee->emergencyContacts->isNotEmpty(),
            'skills' => $employee->skills->isNotEmpty(),
            'education' => $employee->educations->isNotEmpty(),
            'experience' => $employee->experiences->isNotEmpty(),
            'certifications' => $employee->certifications->isNotEmpty(),
            'identity' => $employee->identities->isNotEmpty(),
        ];

        $sectionsConfig = config('hrms.profile_completion.sections', []);
        $sections = [];
        $earned = 0;
        $total = 0;

        foreach ($sectionsConfig as $key => $meta) {
            $weight = (int) ($meta['weight'] ?? 0);
            $complete = (bool) ($checks[$key] ?? false);
            $sections[] = [
                'key' => $key,
                'label' => $meta['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                'complete' => $complete,
                'weight' => $weight,
            ];
            $total += $weight;
            if ($complete) {
                $earned += $weight;
            }
        }

        $percentage = $total > 0 ? (int) round(($earned / $total) * 100) : 0;

        return [
            'percentage' => $percentage,
            'sections' => $sections,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function currentWorkSummary(Employee $employee): array
    {
        $userId = $employee->user_id;
        $empty = [
            'projects' => collect(),
            'assigned_tasks' => 0,
            'open_tasks' => 0,
            'completed_tasks' => 0,
            'hours_logged_this_week' => 0.0,
            'current_sprint' => null,
            'workload' => null,
        ];

        if (! $userId) {
            return $empty;
        }

        $projects = ProjectMember::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereNull('left_at')
            ->with('project:id,name,code,status')
            ->latest('joined_at')
            ->limit(10)
            ->get();

        $tasks = Task::query()
            ->where('assigned_to', $userId)
            ->get(['id', 'status', 'sprint_id', 'actual_hours', 'estimated_hours', 'completed_at']);

        $openStatuses = ['backlog', 'todo', 'in_progress', 'review', 'testing', 'blocked'];
        $openTasks = $tasks->filter(fn (Task $t) => in_array($t->status, $openStatuses, true)
            || ($t->completed_at === null && ! in_array($t->status, ['completed', 'cancelled', 'done'], true)));
        $completedTasks = $tasks->filter(fn (Task $t) => in_array($t->status, ['completed', 'done'], true)
            || $t->completed_at !== null);

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $hoursLogged = (float) Task::query()
            ->where('assigned_to', $userId)
            ->whereNotNull('updated_at')
            ->whereBetween('updated_at', [$weekStart, $weekEnd])
            ->sum('actual_hours');

        $activeSprintId = $tasks
            ->whereNotNull('sprint_id')
            ->pluck('sprint_id')
            ->unique()
            ->first();

        $currentSprint = null;
        if ($activeSprintId && Schema::hasTable('sprints')) {
            $currentSprint = Sprint::query()
                ->with('project:id,name,code')
                ->where('id', $activeSprintId)
                ->whereIn('status', ['active', 'planned', 'in_progress'])
                ->first();

            if (! $currentSprint) {
                $currentSprint = Sprint::query()
                    ->with('project:id,name,code')
                    ->whereHas('tasks', fn ($q) => $q->where('assigned_to', $userId))
                    ->whereIn('status', ['active', 'in_progress'])
                    ->latest('start_date')
                    ->first();
            }
        }

        $from = now()->startOfWeek();
        $to = now()->endOfWeek();
        $workload = null;
        try {
            $calc = $this->workloadService->calculateForEmployee($employee, $from, $to);
            $workload = [
                'allocated' => $calc['allocated'] ?? 0,
                'available' => $calc['available'] ?? 0,
                'utilization' => $calc['utilization'] ?? null,
                'status' => $calc['status'] ?? $calc['display_status'] ?? null,
            ];
        } catch (\Throwable) {
            $workload = null;
        }

        return [
            'projects' => $projects,
            'assigned_tasks' => $tasks->count(),
            'open_tasks' => $openTasks->count(),
            'completed_tasks' => $completedTasks->count(),
            'hours_logged_this_week' => round($hoursLogged, 2),
            'current_sprint' => $currentSprint,
            'workload' => $workload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceSummary(Employee $employee, ?int $year = null, ?int $month = null): array
    {
        $year ??= (int) now()->year;
        $month ??= (int) now()->month;

        $monthData = $this->attendanceCalendar->monthForEmployee($employee, $year, $month);
        $summary = $monthData['summary'] ?? [];

        $present = (int) ($summary['present'] ?? 0);
        $late = (int) ($summary['late'] ?? 0);
        $halfDay = (int) ($summary['half_day'] ?? 0);
        $leave = (int) ($summary['leave_approved'] ?? $summary['on_leave'] ?? 0);
        $absent = (int) ($summary['absent'] ?? 0);
        $holiday = (int) ($summary['holiday'] ?? 0);
        $weekend = (int) ($summary['weekend'] ?? 0);

        $workedLike = $present + $late + ($halfDay * 0.5);
        $expected = $present + $late + $halfDay + $leave + $absent;
        $percentage = $expected > 0 ? (int) round(($workedLike / $expected) * 100) : 0;

        return [
            'year' => $year,
            'month' => $month,
            'present' => $present,
            'late' => $late,
            'half_day' => $halfDay,
            'leave' => $leave,
            'absent' => $absent,
            'holiday' => $holiday,
            'weekend' => $weekend,
            'attendance_percentage' => $percentage,
            'raw' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function leaveSummary(Employee $employee, ?int $year = null): array
    {
        $year ??= (int) now()->year;
        $balances = $this->leaveService->getBalancesForEmployee($employee, $year);

        $pending = LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->with('leaveType')
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        $approved = LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->with('leaveType')
            ->latest('start_date')
            ->limit(10)
            ->get();

        $upcoming = LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '>=', now()->toDateString())
            ->with('leaveType')
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        $history = LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'rejected', 'cancelled'])
            ->with('leaveType')
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        return [
            'balances' => $balances,
            'pending' => $pending,
            'approved' => $approved,
            'upcoming' => $upcoming,
            'history' => $history,
        ];
    }

    /**
     * @return array{
     *     reporting_manager: ?Employee,
     *     department_head: ?Employee,
     *     hr_contact: ?Employee,
     *     direct_reportees: Collection<int, Employee>
     * }
     */
    public function reportingStructure(Employee $employee): array
    {
        $employee->loadMissing(['reportingManager.designation', 'department', 'directReports.designation']);

        $departmentHead = null;
        if ($employee->department_id) {
            $departmentHead = Employee::query()
                ->where('department_id', $employee->department_id)
                ->where('id', '!=', $employee->id)
                ->whereIn('status', ['active', 'probation', 'notice_period'])
                ->whereHas('designation', function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('name', 'like', '%head%')
                            ->orWhere('name', 'like', '%director%')
                            ->orWhere('code', 'like', '%HEAD%');
                    });
                })
                ->with('designation')
                ->orderBy('first_name')
                ->first();

            if (! $departmentHead) {
                // Walk up the reporting chain within the same department.
                $cursor = $employee->reportingManager;
                while ($cursor !== null) {
                    if ((int) $cursor->department_id === (int) $employee->department_id) {
                        $departmentHead = $cursor;
                    }
                    $cursor = $cursor->reporting_manager_id
                        ? Employee::query()->with('designation')->find($cursor->reporting_manager_id)
                        : null;
                }
            }
        }

        $hrContact = Employee::query()
            ->where('id', '!=', $employee->id)
            ->whereIn('status', ['active', 'probation', 'notice_period'])
            ->whereHas('user.organizations', function ($q) use ($employee) {
                $q->where('organizations.id', $employee->organization_id)
                    ->whereIn('organization_user.role', ['hr', 'hr_manager', 'admin', 'organization-owner']);
            })
            ->with(['designation', 'user'])
            ->orderBy('first_name')
            ->first();

        if (! $hrContact) {
            $hrContact = Employee::query()
                ->where('id', '!=', $employee->id)
                ->whereIn('status', ['active', 'probation'])
                ->whereHas('designation', fn ($q) => $q->where('name', 'like', '%hr%')->orWhere('code', 'like', '%HR%'))
                ->with('designation')
                ->orderBy('first_name')
                ->first();
        }

        return [
            'reporting_manager' => $employee->reportingManager,
            'department_head' => $departmentHead,
            'hr_contact' => $hrContact,
            'direct_reportees' => $employee->directReports()
                ->with('designation')
                ->orderBy('first_name')
                ->get(),
        ];
    }

    /** @return Collection<int, Holiday> */
    public function upcomingHolidays(Employee $employee, int $limit = 5): Collection
    {
        if (! Schema::hasTable('holidays')) {
            return collect();
        }

        return $this->leaveService->getHolidaysForEmployee($employee, (int) now()->year)
            ->filter(fn (Holiday $h) => $h->holiday_date && $h->holiday_date->gte(now()->startOfDay()))
            ->sortBy(fn (Holiday $h) => $h->holiday_date->timestamp)
            ->take($limit)
            ->values();
    }

    /**
     * Aggregate profile payload for show / widgets.
     *
     * @return array<string, mixed>
     */
    public function profileDashboard(Employee $employee): array
    {
        return [
            'completion' => $this->profileCompletion($employee),
            'work' => $this->currentWorkSummary($employee),
            'attendance' => $this->attendanceSummary($employee),
            'leave' => $this->leaveSummary($employee),
            'reporting' => $this->reportingStructure($employee),
            'upcoming_holidays' => $this->upcomingHolidays($employee),
        ];
    }

    public function assertCanEditProfile(Employee $employee, User $actor, bool $allowSelf = true): void
    {
        if ($actor->can('update', $employee)) {
            return;
        }

        if ($allowSelf && $actor->can('updateOwn', $employee)) {
            return;
        }

        abort(403);
    }
}
