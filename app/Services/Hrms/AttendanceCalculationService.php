<?php

namespace App\Services\Hrms;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HrmsShift;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AttendanceCalculationService
{
    public function __construct(
        protected WorkingTimeCalculator $workingTimeCalculator,
        protected LeaveService $leaveService,
    ) {}

    /**
     * @return array{
     *     working_minutes: int,
     *     break_minutes: int,
     *     late_minutes: int,
     *     early_departure_minutes: int,
     *     overtime_minutes: int,
     *     status: string
     * }
     */
    public function calculateMetrics(AttendanceRecord $record, HrmsShift $shift): array
    {
        $result = $this->workingTimeCalculator->calculate(
            $record->clock_in_at,
            $record->clock_out_at,
            $record->attendance_date,
            $this->shiftToArray($shift),
            $record->status ?? 'pending',
        );

        return [
            'working_minutes' => $result['working_minutes'],
            'break_minutes' => $result['break_minutes'],
            'late_minutes' => $result['late_minutes'],
            'early_departure_minutes' => $result['early_departure_minutes'],
            'overtime_minutes' => $result['overtime_minutes'],
            'status' => $result['status'],
        ];
    }

    /**
     * Apply leave / holiday context overrides when clocks are absent.
     *
     * @return array{status: string, on_leave: bool, is_holiday: bool, is_weekend: bool}
     */
    public function resolveDayContext(Employee $employee, CarbonInterface $date): array
    {
        $day = Carbon::parse($date)->startOfDay();
        $onLeave = $this->leaveService->getApprovedLeaveForDate($employee, $day)->isNotEmpty();
        $isHoliday = $this->isHolidayForEmployee($employee, $day);
        $isWeekend = $this->isWeekend($day);

        $status = 'absent';
        if ($onLeave) {
            $status = 'on_leave';
        } elseif ($isHoliday) {
            $status = 'holiday';
        } elseif ($isWeekend) {
            $status = 'weekend';
        }

        return [
            'status' => $status,
            'on_leave' => $onLeave,
            'is_holiday' => $isHoliday,
            'is_weekend' => $isWeekend,
        ];
    }

    public function isHolidayForEmployee(Employee $employee, CarbonInterface $date): bool
    {
        return Holiday::query()
            ->where('organization_id', $employee->organization_id)
            ->whereDate('holiday_date', $date)
            ->where(function ($query) use ($employee) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $employee->branch_id);
            })
            ->exists();
    }

    public function isWeekend(CarbonInterface $date): bool
    {
        return in_array(
            strtolower(Carbon::parse($date)->englishDayOfWeek),
            config('hrms.weekend_days', []),
            true
        );
    }

    /**
     * Half-day helper: working minutes below minimum but at/above half threshold.
     */
    public function isHalfDay(int $workingMinutes, int $minimumWorkingMinutes): bool
    {
        if ($minimumWorkingMinutes <= 0) {
            return false;
        }

        return $workingMinutes < $minimumWorkingMinutes;
    }

    /** @return array<string, mixed> */
    public function shiftToArray(HrmsShift $shift): array
    {
        return [
            'start_time' => (string) $shift->start_time,
            'end_time' => (string) $shift->end_time,
            'break_minutes' => (int) $shift->break_minutes,
            'grace_period_minutes' => (int) $shift->grace_period_minutes,
            'late_threshold_minutes' => $shift->late_threshold_minutes,
            'early_exit_threshold_minutes' => $shift->early_exit_threshold_minutes,
            'working_hours' => $shift->working_hours,
            'minimum_working_minutes' => $shift->minimum_working_minutes,
            'overtime_threshold_minutes' => $shift->overtime_threshold_minutes,
            'maximum_working_minutes' => $shift->maximum_working_minutes,
            'overtime_allowed' => $shift->overtime_allowed ?? true,
            'is_overnight' => (bool) $shift->is_overnight,
        ];
    }
}
