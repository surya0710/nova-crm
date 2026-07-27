<?php

namespace App\Services\Hrms;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HrmsShift;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AttendanceDashboardService
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function employeeSummary(Employee $employee, ?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $record = $this->recordForDate($employee, $date);
        $shift = $this->attendanceService->resolveShiftForEmployee($employee, $date);
        $state = $this->resolveState($employee, $date, $record);
        $working = $this->workingHours($record, $shift);
        $indicator = $this->attendanceIndicator($record, $shift, $date, $state);
        $shiftInfo = $this->shiftInfo($shift, $employee, $date, $record);
        $actions = $this->clockActions($employee, $date, $record, $state);

        return [
            'date' => $date->toDateString(),
            'state' => $state,
            'state_label' => $this->stateLabel($state),
            'record' => $record,
            'shift' => $shift,
            'shift_info' => $shiftInfo,
            'working_hours' => $working,
            'indicator' => $indicator,
            'actions' => $actions,
            'recent_attendance' => $this->recentAttendance($employee, 5),
            'upcoming_holidays' => $this->upcomingHolidays($employee, 5),
            'on_leave_today' => $state === 'on_leave',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function teamSummary(Employee $manager, ?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $cacheKey = sprintf('attendance.team_summary.%d.%s', $manager->organization_id, $date->toDateString());

        return Cache::remember($cacheKey, 60, function () use ($manager, $date) {
            $teamIds = Employee::query()
                ->where('reporting_manager_id', $manager->id)
                ->whereIn('status', config('hrms.clockable_employee_statuses', []))
                ->pluck('id');

            $teamCount = $teamIds->count();

            if ($teamCount === 0) {
                return $this->emptyTeamSummary($date);
            }

            $records = AttendanceRecord::query()
                ->whereIn('employee_id', $teamIds)
                ->whereDate('attendance_date', $date)
                ->get();

            $onLeaveIds = LeaveApplication::query()
                ->whereIn('employee_id', $teamIds)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->pluck('employee_id');

            $present = $records->whereIn('status', ['present', 'late', 'half_day'])->count();
            $late = $records->filter(fn (AttendanceRecord $r) => $r->status === 'late' || (int) $r->late_minutes > 0)->count();
            $working = $records->filter(fn (AttendanceRecord $r) => $r->clock_in_at !== null && $r->clock_out_at === null)->count();
            $checkedOut = $records->filter(fn (AttendanceRecord $r) => $r->clock_out_at !== null)->count();
            $leave = $onLeaveIds->unique()->count();
            $clockedInIds = $records->whereNotNull('clock_in_at')->pluck('employee_id');
            $absent = max(0, $teamCount - $clockedInIds->merge($onLeaveIds)->unique()->count());

            $notCheckedIn = $teamIds->diff($clockedInIds)->diff($onLeaveIds)->values();

            $lateEmployees = AttendanceRecord::query()
                ->whereIn('employee_id', $teamIds)
                ->whereDate('attendance_date', $date)
                ->where(function ($query) {
                    $query->where('status', 'late')
                        ->orWhere('late_minutes', '>', 0);
                })
                ->with('employee:id,first_name,last_name,employee_code')
                ->limit(10)
                ->get()
                ->map(fn (AttendanceRecord $r) => [
                    'employee_id' => $r->employee_id,
                    'name' => $r->employee?->full_name,
                    'late_minutes' => $r->late_minutes,
                    'clock_in_at' => $r->clock_in_at?->toIso8601String(),
                ])
                ->values()
                ->all();

            return [
                'date' => $date->toDateString(),
                'team_count' => $teamCount,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'late' => $late,
                'working' => $working,
                'checked_out' => $checkedOut,
                'not_checked_in_count' => $notCheckedIn->count(),
                'late_employees' => $lateEmployees,
                'attendance_url' => route('hrms.attendance.index'),
            ];
        });
    }

    public function resolveState(Employee $employee, Carbon $date, ?AttendanceRecord $record = null): string
    {
        $record ??= $this->recordForDate($employee, $date);

        if ($this->attendanceService->isEmployeeOnLeave($employee, $date)) {
            return 'on_leave';
        }

        if ($this->attendanceService->isHolidayForEmployee($employee, $date)) {
            return 'holiday';
        }

        if ($this->attendanceService->isWeekend($date)) {
            return 'weekend';
        }

        if ($record === null || $record->clock_in_at === null) {
            return 'not_checked_in';
        }

        if ($record->clock_out_at !== null) {
            return 'checked_out';
        }

        return 'checked_in';
    }

    /**
     * @return array<string, mixed>
     */
    public function workingHours(?AttendanceRecord $record, ?HrmsShift $shift): array
    {
        $workedMinutes = 0;
        $isLive = false;

        if ($record?->clock_in_at) {
            if ($record->clock_out_at !== null && $record->working_minutes !== null) {
                $workedMinutes = (int) $record->working_minutes;
            } else {
                $end = $record->clock_out_at ?? now();
                $gross = (int) $record->clock_in_at->diffInMinutes($end);
                $break = $record->clock_out_at ? (int) ($shift?->break_minutes ?? 0) : 0;
                $workedMinutes = max(0, $gross - $break);
                $isLive = $record->clock_out_at === null;
            }
        }

        $expectedMinutes = $shift !== null
            ? (int) round(((float) $shift->working_hours) * 60)
            : null;

        $remainingMinutes = $expectedMinutes !== null
            ? max(0, $expectedMinutes - $workedMinutes)
            : null;

        return [
            'worked_minutes' => $workedMinutes,
            'worked_label' => $this->formatDuration($workedMinutes),
            'expected_minutes' => $expectedMinutes,
            'expected_label' => $expectedMinutes !== null ? $this->formatDuration($expectedMinutes) : null,
            'remaining_minutes' => $remainingMinutes,
            'remaining_label' => $remainingMinutes !== null ? $this->formatDuration($remainingMinutes) : null,
            'overtime_minutes' => $record?->overtime_minutes ?? 0,
            'is_live' => $isLive,
            'clock_in_at' => $record?->clock_in_at?->toIso8601String(),
            'clock_out_at' => $record?->clock_out_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{key: string, label: string, color: string}|null
     */
    public function attendanceIndicator(
        ?AttendanceRecord $record,
        ?HrmsShift $shift,
        Carbon $date,
        string $state,
    ): ?array {
        if (in_array($state, ['on_leave', 'holiday', 'weekend', 'not_checked_in'], true)) {
            return null;
        }

        if ($record === null) {
            return null;
        }

        if ($record->clock_in_at !== null && $record->clock_out_at === null && $shift !== null) {
            $shiftEnd = $this->shiftEndAt($shift, $date);
            if (now()->gt($shiftEnd)) {
                return ['key' => 'missing_checkout', 'label' => __('Missing Checkout'), 'color' => 'red'];
            }
        }

        if ($record->late_minutes > 0 || $record->status === 'late') {
            return ['key' => 'late', 'label' => __('Late'), 'color' => 'orange'];
        }

        if ($record->early_departure_minutes > 0) {
            return ['key' => 'early', 'label' => __('Early Departure'), 'color' => 'yellow'];
        }

        if ($record->clock_in_at !== null && $shift !== null) {
            $shiftStart = $this->shiftStartAt($shift, $date);
            $graceEnd = $shiftStart->copy()->addMinutes((int) $shift->grace_period_minutes);
            if ($record->clock_in_at->lt($shiftStart)) {
                return ['key' => 'early', 'label' => __('Early'), 'color' => 'yellow'];
            }
            if ($record->clock_in_at->lte($graceEnd)) {
                return ['key' => 'on_time', 'label' => __('On Time'), 'color' => 'green'];
            }
        }

        if ($record->clock_in_at !== null) {
            return ['key' => 'on_time', 'label' => __('On Time'), 'color' => 'green'];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function shiftInfo(?HrmsShift $shift, Employee $employee, Carbon $date, ?AttendanceRecord $record): array
    {
        if ($shift === null) {
            return [
                'available' => false,
                'phase' => null,
            ];
        }

        $shiftStart = $this->shiftStartAt($shift, $date);
        $shiftEnd = $this->shiftEndAt($shift, $date);
        $now = now();

        $phase = 'upcoming';
        if ($now->gte($shiftStart) && $now->lte($shiftEnd) && ($record === null || $record->clock_out_at === null)) {
            $phase = 'current';
        } elseif ($now->gt($shiftEnd) || ($record !== null && $record->clock_out_at !== null)) {
            $phase = 'completed';
        }

        return [
            'available' => true,
            'name' => $shift->name,
            'code' => $shift->code,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'break_minutes' => $shift->break_minutes,
            'branch' => $employee->branch?->name,
            'phase' => $phase,
            'phase_label' => match ($phase) {
                'current' => __('Current shift'),
                'completed' => __('Shift completed'),
                default => __('Upcoming shift'),
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function clockActions(Employee $employee, Carbon $date, ?AttendanceRecord $record, string $state): array
    {
        $canCheckIn = $state === 'not_checked_in';
        $canCheckOut = $state === 'checked_in';

        return [
            'can_check_in' => $canCheckIn,
            'can_check_out' => $canCheckOut,
            'check_in_url' => route('ess.attendance.clock-in'),
            'check_out_url' => route('ess.attendance.clock-out'),
            'blocked_reason' => match ($state) {
                'on_leave' => __('You are on approved leave today.'),
                'holiday' => __('Today is a holiday.'),
                'weekend' => __('Attendance is not required on weekends.'),
                'checked_out' => __('You have already checked out for today.'),
                default => null,
            },
        ];
    }

    /** @return Collection<int, AttendanceRecord> */
    protected function recentAttendance(Employee $employee, int $limit = 5): Collection
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->with('shift')
            ->latest('attendance_date')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Holiday> */
    protected function upcomingHolidays(Employee $employee, int $limit = 5): Collection
    {
        $today = now()->startOfDay();

        return Holiday::query()
            ->where('organization_id', $employee->organization_id)
            ->where(function ($query) use ($employee) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $employee->branch_id);
            })
            ->whereDate('holiday_date', '>=', $today)
            ->orderBy('holiday_date')
            ->limit($limit)
            ->get();
    }

    protected function recordForDate(Employee $employee, Carbon $date): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->with('shift')
            ->first();
    }

    protected function shiftStartAt(HrmsShift $shift, Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString((string) $shift->start_time);
    }

    protected function shiftEndAt(HrmsShift $shift, Carbon $date): Carbon
    {
        $end = $date->copy()->setTimeFromTimeString((string) $shift->end_time);

        if ($shift->is_overnight || $end->lte($this->shiftStartAt($shift, $date))) {
            $end->addDay();
        }

        return $end;
    }

    protected function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $mins);
        }

        return sprintf('%dm', $mins);
    }

    protected function stateLabel(string $state): string
    {
        return match ($state) {
            'not_checked_in' => __('Not Checked In'),
            'checked_in' => __('Checked In'),
            'checked_out' => __('Checked Out'),
            'on_leave' => __('On Leave'),
            'holiday' => __('Holiday'),
            'weekend' => __('Weekend'),
            default => ucfirst(str_replace('_', ' ', $state)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyTeamSummary(Carbon $date): array
    {
        return [
            'date' => $date->toDateString(),
            'team_count' => 0,
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
            'late' => 0,
            'working' => 0,
            'checked_out' => 0,
            'not_checked_in_count' => 0,
            'late_employees' => [],
            'attendance_url' => route('hrms.attendance.index'),
        ];
    }
}
