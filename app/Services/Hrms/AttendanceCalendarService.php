<?php

namespace App\Services\Hrms;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceCalendarService
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected AttendanceDashboardService $dashboardService,
        protected LeaveService $leaveService,
        protected WfhPolicyService $wfhPolicyService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function monthForEmployee(Employee $employee, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = $this->recordsForRange($employee, $start, $end);
        $holidays = $this->holidaysForRange($employee, $start, $end);
        $approvedLeave = $this->leaveService->getApprovedLeaveForDateRange($employee, $start, $end);
        $pendingLeave = $this->pendingLeaveForRange($employee, $start, $end);

        $days = [];
        $summary = $this->emptySummary();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dateKey = $cursor->toDateString();
            $record = $records->get($dateKey);
            $holiday = $holidays->get($dateKey);
            $leave = $this->leaveForDate($approvedLeave, $pendingLeave, $cursor);
            $day = $this->buildDay($employee, $cursor, $record, $holiday, $leave);
            $days[] = $day;
            $this->accumulateSummary($summary, $day, $cursor);
            $cursor->addDay();
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_label' => $start->format('F Y'),
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'code' => $employee->employee_code,
            ],
            'days' => $days,
            'summary' => $summary,
            'leave_balances' => $this->serializeLeaveBalances($employee, $year),
            'timeline' => $this->recentTimeline($employee, $records),
            'legend' => $this->legend(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function teamMonthForManager(Employee $manager, int $year, int $month): array
    {
        $team = Employee::query()
            ->where('reporting_manager_id', $manager->id)
            ->whereIn('status', config('hrms.clockable_employee_statuses', []))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code']);

        $members = $team->map(function (Employee $member) use ($year, $month) {
            $monthData = $this->monthForEmployee($member, $year, $month);

            return [
                'employee' => $monthData['employee'],
                'summary' => $monthData['summary'],
                'days' => collect($monthData['days'])->map(fn (array $day) => [
                    'date' => $day['date'],
                    'visual' => $day['visual'],
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'year' => $year,
            'month' => $month,
            'month_label' => Carbon::create($year, $month, 1)->format('F Y'),
            'members' => $members,
            'legend' => $this->legend(),
        ];
    }

    /**
     * @return list<array{key: string, label: string, color: string, symbol: string}>
     */
    public function legend(): array
    {
        return [
            ['key' => 'present', 'label' => __('Present'), 'color' => 'emerald', 'symbol' => '🟢'],
            ['key' => 'absent', 'label' => __('Absent'), 'color' => 'red', 'symbol' => '🔴'],
            ['key' => 'leave_approved', 'label' => __('Approved Leave'), 'color' => 'blue', 'symbol' => '🔵'],
            ['key' => 'leave_pending', 'label' => __('Pending Leave'), 'color' => 'purple', 'symbol' => '🟣'],
            ['key' => 'holiday', 'label' => __('Public Holiday'), 'color' => 'orange', 'symbol' => '🟠'],
            ['key' => 'weekend', 'label' => __('Weekend'), 'color' => 'slate', 'symbol' => '⚪'],
            ['key' => 'half_day', 'label' => __('Half Day'), 'color' => 'amber', 'symbol' => '🟡'],
            ['key' => 'remote', 'label' => __('WFH'), 'color' => 'cyan', 'symbol' => '🏠'],
            ['key' => 'late', 'label' => __('Late'), 'color' => 'yellow', 'symbol' => '🟡'],
            ['key' => 'missing_checkout', 'label' => __('Missing Checkout'), 'color' => 'neutral', 'symbol' => '⚫'],
        ];
    }

    /**
     * @return Collection<int, Employee>
     */
    public function filterableEmployees(?int $departmentId = null): Collection
    {
        $query = Employee::query()
            ->whereIn('status', config('hrms.clockable_employee_statuses', []))
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        return $query->get(['id', 'first_name', 'last_name', 'employee_code', 'department_id']);
    }

    /**
     * @return Collection<int, Employee>
     */
    public function teamEmployeesForManager(Employee $manager): Collection
    {
        return Employee::query()
            ->where('reporting_manager_id', $manager->id)
            ->whereIn('status', config('hrms.clockable_employee_statuses', []))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code', 'department_id']);
    }

    /**
     * @return Collection<int, Department>
     */
    public function filterableDepartments(): Collection
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /** @return Collection<string, AttendanceRecord> */
    protected function recordsForRange(Employee $employee, Carbon $start, Carbon $end): Collection
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->with('shift')
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->attendance_date->toDateString());
    }

    /** @return Collection<string, Holiday> */
    protected function holidaysForRange(Employee $employee, Carbon $start, Carbon $end): Collection
    {
        return Holiday::query()
            ->where('organization_id', $employee->organization_id)
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) use ($employee): void {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $employee->branch_id);
            })
            ->get()
            ->keyBy(fn (Holiday $holiday) => $holiday->holiday_date->toDateString());
    }

    /** @return Collection<int, LeaveApplication> */
    protected function pendingLeaveForRange(Employee $employee, Carbon $start, Carbon $end): Collection
    {
        return LeaveApplication::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($inner) use ($start, $end): void {
                        $inner->where('start_date', '<=', $start->toDateString())
                            ->where('end_date', '>=', $end->toDateString());
                    });
            })
            ->with('leaveType')
            ->get();
    }

    /**
     * @param  Collection<int, LeaveApplication>  $approvedLeave
     * @param  Collection<int, LeaveApplication>  $pendingLeave
     * @return array<string, mixed>|null
     */
    protected function leaveForDate(Collection $approvedLeave, Collection $pendingLeave, Carbon $date): ?array
    {
        foreach ($approvedLeave as $application) {
            if ($date->between($application->start_date, $application->end_date)) {
                return $this->serializeLeave($application, 'approved');
            }
        }

        foreach ($pendingLeave as $application) {
            if ($date->between($application->start_date, $application->end_date)) {
                return $this->serializeLeave($application, 'pending');
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDay(
        Employee $employee,
        Carbon $date,
        ?AttendanceRecord $record,
        ?Holiday $holiday,
        ?array $leave,
    ): array {
        $isWeekend = $this->attendanceService->isWeekend($date);
        $isFuture = $date->isAfter(now()->startOfDay());
        $state = $this->dashboardService->resolveState($employee, $date, $record);
        $shift = $this->attendanceService->resolveShiftForEmployee($employee, $date);
        $indicator = $this->dashboardService->attendanceIndicator($record, $shift, $date, $state);
        $working = $this->dashboardService->workingHours($record, $shift);
        $visual = $this->resolveVisual(
            $date,
            $state,
            $record,
            $holiday,
            $leave,
            $isWeekend,
            $isFuture,
            $indicator,
            $this->wfhPolicyService->isWfhDay($employee, $date),
        );

        return [
            'date' => $date->toDateString(),
            'day' => (int) $date->day,
            'is_today' => $date->isToday(),
            'is_future' => $isFuture,
            'is_weekend' => $isWeekend,
            'state' => $state,
            'visual' => $visual,
            'holiday' => $holiday ? [
                'name' => $holiday->name,
                'is_optional' => (bool) $holiday->is_optional,
                'type' => $holiday->is_optional ? __('Optional holiday') : __('Public holiday'),
            ] : null,
            'leave' => $leave,
            'attendance' => $record ? [
                'status' => $record->status,
                'status_label' => $record->statusLabel(),
                'clock_in' => $record->clock_in_at?->format('g:i A'),
                'clock_out' => $record->clock_out_at?->format('g:i A'),
                'working_label' => $working['worked_label'],
                'source' => $record->source,
                'source_label' => $record->sourceLabel(),
                'notes' => $record->notes,
                'late_minutes' => $record->late_minutes,
            ] : null,
            'shift' => $shift ? [
                'name' => $shift->name,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
            ] : null,
            'indicator' => $indicator,
            'timeline' => $this->buildDayTimeline($date, $record, $holiday, $leave, $shift, $indicator, $visual),
        ];
    }

    /**
     * @return list<array{time: string|null, label: string, type: string}>
     */
    protected function buildDayTimeline(
        Carbon $date,
        ?AttendanceRecord $record,
        ?Holiday $holiday,
        ?array $leave,
        mixed $shift,
        ?array $indicator,
        array $visual,
    ): array {
        $entries = [];

        if ($holiday !== null) {
            $entries[] = [
                'time' => null,
                'label' => $holiday->name,
                'type' => 'holiday',
            ];
        }

        if ($leave !== null) {
            $entries[] = [
                'time' => null,
                'label' => trim(($leave['type'] ?? __('Leave')).' · '.($leave['status_label'] ?? '')),
                'type' => 'leave',
            ];
        }

        if ($shift !== null) {
            $entries[] = [
                'time' => null,
                'label' => __('Shift').': '.$shift->name,
                'type' => 'shift',
            ];
        }

        if ($record?->clock_in_at !== null) {
            $entries[] = [
                'time' => $record->clock_in_at->format('g:i A'),
                'label' => __('Check In'),
                'type' => 'check_in',
            ];
        }

        if ($indicator !== null && $indicator['key'] === 'late') {
            $entries[] = [
                'time' => $record?->clock_in_at?->format('g:i A'),
                'label' => $indicator['label'] ?? __('Late'),
                'type' => 'late',
            ];
        }

        if ($record?->clock_out_at !== null) {
            $entries[] = [
                'time' => $record->clock_out_at->format('g:i A'),
                'label' => __('Check Out'),
                'type' => 'check_out',
            ];
        }

        if ($indicator !== null && $indicator['key'] === 'missing_checkout') {
            $entries[] = [
                'time' => null,
                'label' => $indicator['label'] ?? __('Missing Checkout'),
                'type' => 'missing_checkout',
            ];
        }

        if ($visual['key'] === 'weekend' && $record === null) {
            $entries[] = [
                'time' => null,
                'label' => __('Weekend'),
                'type' => 'weekend',
            ];
        }

        if ($visual['key'] === 'absent') {
            $entries[] = [
                'time' => null,
                'label' => __('Absent'),
                'type' => 'absent',
            ];
        }

        if ($visual['key'] === 'remote' && $record?->clock_in_at !== null) {
            $entries[] = [
                'time' => null,
                'label' => __('WFH / Remote Work'),
                'type' => 'remote',
            ];
        }

        return $entries;
    }

    /**
     * @return array{key: string, label: string, color: string, dots: list<string>, border: string|null}
     */
    protected function resolveVisual(
        Carbon $date,
        string $state,
        ?AttendanceRecord $record,
        ?Holiday $holiday,
        ?array $leave,
        bool $isWeekend,
        bool $isFuture,
        ?array $indicator,
        bool $isWfh = false,
    ): array {
        $dots = [];

        if ($indicator !== null) {
            if ($indicator['key'] === 'late') {
                $dots[] = 'late';
            }
            if ($indicator['key'] === 'missing_checkout') {
                $dots[] = 'missing_checkout';
            }
        }

        if ($isFuture && $leave === null && $record === null && $holiday === null && ! $isWeekend) {
            return [
                'key' => 'future',
                'label' => __('Future'),
                'color' => 'default',
                'dots' => $dots,
                'border' => null,
            ];
        }

        if ($leave !== null) {
            $border = match ($leave['status']) {
                'rejected' => 'red',
                'cancelled' => 'slate',
                default => null,
            };

            return [
                'key' => $leave['status'] === 'pending' ? 'leave_pending' : 'leave_approved',
                'label' => $leave['status'] === 'pending' ? __('Pending Leave') : __('Approved Leave'),
                'color' => $leave['status'] === 'pending' ? 'purple' : 'blue',
                'dots' => $dots,
                'border' => $border,
            ];
        }

        if ($holiday !== null && ($record === null || $record->clock_in_at === null)) {
            return [
                'key' => 'holiday',
                'label' => __('Holiday'),
                'color' => 'orange',
                'dots' => $dots,
                'border' => null,
            ];
        }

        if ($record !== null && $record->clock_in_at !== null) {
            if ($record->status === 'half_day') {
                return [
                    'key' => 'half_day',
                    'label' => __('Half Day'),
                    'color' => 'amber',
                    'dots' => $dots,
                    'border' => null,
                ];
            }

            if ($isWfh || in_array($record->source, ['mobile', 'api'], true)) {
                return [
                    'key' => 'remote',
                    'label' => __('WFH'),
                    'color' => 'cyan',
                    'dots' => $dots,
                    'border' => null,
                ];
            }

            if (in_array($record->status, ['present', 'late'], true)) {
                return [
                    'key' => $record->status === 'late' ? 'late' : 'present',
                    'label' => $record->status === 'late' ? __('Late') : __('Present'),
                    'color' => $record->status === 'late' ? 'yellow' : 'emerald',
                    'dots' => $dots,
                    'border' => null,
                ];
            }
        }

        if ($isWeekend && ($record === null || $record->clock_in_at === null)) {
            return [
                'key' => 'weekend',
                'label' => __('Weekend'),
                'color' => 'slate',
                'dots' => $dots,
                'border' => null,
            ];
        }

        // Planned/active WFH day without punch still surfaces as WFH (not absent).
        if ($isWfh && ($record === null || $record->clock_in_at === null)) {
            return [
                'key' => 'remote',
                'label' => __('WFH'),
                'color' => 'cyan',
                'dots' => $dots,
                'border' => null,
            ];
        }

        if (! $isFuture && ! $isWeekend && $holiday === null && ($record === null || $record->clock_in_at === null)) {
            return [
                'key' => 'absent',
                'label' => __('Absent'),
                'color' => 'red',
                'dots' => $dots,
                'border' => null,
            ];
        }

        if ($isWeekend) {
            return [
                'key' => 'weekend',
                'label' => __('Weekend'),
                'color' => 'slate',
                'dots' => $dots,
                'border' => null,
            ];
        }

        return [
            'key' => 'default',
            'label' => __('—'),
            'color' => 'default',
            'dots' => $dots,
            'border' => null,
        ];
    }

    /**
     * @param  array<string, int>  $summary
     * @param  array<string, mixed>  $day
     */
    protected function accumulateSummary(array &$summary, array $day, Carbon $date): void
    {
        $key = $day['visual']['key'];

        if ($key === 'weekend') {
            $summary['weekend']++;
        } elseif (isset($summary[$key])) {
            $summary[$key]++;
        }

        foreach ($day['visual']['dots'] as $dot) {
            if ($dot === 'late' && $key !== 'late') {
                $summary['late']++;
            } elseif (isset($summary[$dot]) && $dot !== 'late') {
                $summary[$dot]++;
            }
        }
    }

    /** @return array<string, int> */
    protected function emptySummary(): array
    {
        return [
            'present' => 0,
            'absent' => 0,
            'leave_approved' => 0,
            'leave_pending' => 0,
            'holiday' => 0,
            'weekend' => 0,
            'late' => 0,
            'half_day' => 0,
            'remote' => 0,
            'missing_checkout' => 0,
        ];
    }

    /** @return list<array<string, mixed>> */
    protected function serializeLeaveBalances(Employee $employee, int $year): array
    {
        return $this->leaveService->getBalancesForEmployee($employee, $year)
            ->map(fn ($balance) => [
                'leave_type' => $balance->leaveType->name,
                'code' => $balance->leaveType->code,
                'balance' => (float) $balance->balance,
                'entitled' => (float) $balance->entitled,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<string, AttendanceRecord>  $records
     * @return list<array<string, mixed>>
     */
    protected function recentTimeline(Employee $employee, Collection $records): array
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->latest('attendance_date')
            ->limit(8)
            ->get()
            ->map(function (AttendanceRecord $record) use ($employee) {
                $date = $record->attendance_date->copy()->startOfDay();
                $state = $this->dashboardService->resolveState($employee, $date, $record);
                $label = match (true) {
                    $state === 'on_leave' => __('Approved Leave'),
                    $record->status === 'late' || $record->late_minutes > 0 => __('Late Arrival'),
                    $record->clock_in_at !== null && $record->clock_out_at === null => __('Checked In'),
                    $record->clock_out_at !== null => __('Checked Out'),
                    default => $record->statusLabel(),
                };

                return [
                    'date' => $record->attendance_date->format('j M'),
                    'label' => $label,
                    'time' => $record->clock_in_at?->format('g:i A'),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    protected function serializeLeave(LeaveApplication $application, string $status): array
    {
        return [
            'id' => $application->id,
            'status' => $status,
            'status_label' => config('hrms.leave_statuses.'.$status, ucfirst($status)),
            'type' => $application->leaveType?->name,
            'reason' => $application->reason,
            'is_half_day' => (bool) $application->is_half_day,
            'start_date' => $application->start_date->toDateString(),
            'end_date' => $application->end_date->toDateString(),
        ];
    }

    /**
     * @return array{min_year: int, max_year: int, years: list<int>, today_year: int, today_month: int}
     */
    public function navigationConfig(?int $referenceYear = null): array
    {
        $referenceYear ??= (int) now()->year;
        $before = (int) config('hrms.attendance_calendar.year_range_before', 5);
        $after = (int) config('hrms.attendance_calendar.year_range_after', 5);
        $minYear = $referenceYear - $before;
        $maxYear = $referenceYear + $after;

        return [
            'min_year' => $minYear,
            'max_year' => $maxYear,
            'years' => range($minYear, $maxYear),
            'today_year' => (int) now()->year,
            'today_month' => (int) now()->month,
        ];
    }

    /**
     * @return array{year: int, month: int}
     */
    public function normalizeYearMonth(int $year, int $month): array
    {
        $month = max(1, min(12, $month));
        $bounds = $this->navigationConfig();
        $year = max($bounds['min_year'], min($bounds['max_year'], $year));

        return ['year' => $year, 'month' => $month];
    }

    public function appendNavigation(array $payload): array
    {
        $payload['navigation'] = $this->navigationConfig();

        return $payload;
    }
}
