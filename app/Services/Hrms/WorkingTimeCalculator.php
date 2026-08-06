<?php

namespace App\Services\Hrms;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Pure working-time calculation helper.
 * No Eloquent. No database access.
 */
class WorkingTimeCalculator
{
    /**
     * @param  array{
     *     start_time: string,
     *     end_time: string,
     *     break_minutes?: int|null,
     *     grace_period_minutes?: int|null,
     *     late_threshold_minutes?: int|null,
     *     early_exit_threshold_minutes?: int|null,
     *     working_hours?: float|int|string|null,
     *     minimum_working_minutes?: int|null,
     *     overtime_threshold_minutes?: int|null,
     *     maximum_working_minutes?: int|null,
     *     overtime_allowed?: bool|null,
     *     is_overnight?: bool|null
     * }  $shift
     * @return array{
     *     working_minutes: int,
     *     break_minutes: int,
     *     late_minutes: int,
     *     early_departure_minutes: int,
     *     overtime_minutes: int,
     *     gross_minutes: int,
     *     status: string
     * }
     */
    public function calculate(
        ?CarbonInterface $clockIn,
        ?CarbonInterface $clockOut,
        CarbonInterface $attendanceDate,
        array $shift,
        string $fallbackStatus = 'pending',
    ): array {
        if ($clockIn === null || $clockOut === null) {
            return [
                'working_minutes' => 0,
                'break_minutes' => (int) ($shift['break_minutes'] ?? 0),
                'late_minutes' => 0,
                'early_departure_minutes' => 0,
                'overtime_minutes' => 0,
                'gross_minutes' => 0,
                'status' => $fallbackStatus,
            ];
        }

        $date = Carbon::parse($attendanceDate)->startOfDay();
        $shiftStart = $this->shiftStartAt($shift, $date);
        $shiftEnd = $this->shiftEndAt($shift, $date);
        $graceMinutes = (int) ($shift['grace_period_minutes'] ?? 0);
        $lateThreshold = (int) ($shift['late_threshold_minutes'] ?? $graceMinutes);
        $graceEnd = $shiftStart->copy()->addMinutes($lateThreshold);

        $lateMinutes = $clockIn->gt($graceEnd) ? (int) $graceEnd->diffInMinutes($clockIn) : 0;

        $earlyExitThreshold = (int) ($shift['early_exit_threshold_minutes'] ?? 0);
        $earlyCutoff = $shiftEnd->copy()->subMinutes($earlyExitThreshold);
        $earlyDepartureMinutes = $clockOut->lt($earlyCutoff)
            ? (int) $clockOut->diffInMinutes($shiftEnd)
            : 0;

        $grossMinutes = max(0, (int) $clockIn->diffInMinutes($clockOut));
        $breakMinutes = (int) ($shift['break_minutes'] ?? 0);
        $workingMinutes = max(0, $grossMinutes - $breakMinutes);

        $maximumWorking = $shift['maximum_working_minutes'] ?? null;
        if ($maximumWorking !== null) {
            $workingMinutes = min($workingMinutes, (int) $maximumWorking);
        }

        $overtimeThreshold = $shift['overtime_threshold_minutes']
            ?? (int) round(((float) ($shift['working_hours'] ?? 0)) * 60);
        $overtimeAllowed = $shift['overtime_allowed'] ?? true;
        $overtimeMinutes = $overtimeAllowed
            ? max(0, $workingMinutes - (int) $overtimeThreshold)
            : 0;

        $minimumWorking = $shift['minimum_working_minutes']
            ?? (int) round(((float) ($shift['working_hours'] ?? 0)) * 60);

        $status = 'present';
        if ($workingMinutes < (int) ($minimumWorking / 2)) {
            $status = 'half_day';
        } elseif ($workingMinutes < $minimumWorking) {
            $status = 'half_day';
        } elseif ($lateMinutes > 0) {
            $status = 'late';
        }

        return [
            'working_minutes' => $workingMinutes,
            'break_minutes' => $breakMinutes,
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'gross_minutes' => $grossMinutes,
            'status' => $status,
        ];
    }

    /**
     * @param  array{start_time: string, end_time: string, is_overnight?: bool|null}  $shift
     */
    public function shiftStartAt(array $shift, CarbonInterface $date): Carbon
    {
        return Carbon::parse($date)->copy()->setTimeFromTimeString((string) $shift['start_time']);
    }

    /**
     * @param  array{start_time: string, end_time: string, is_overnight?: bool|null}  $shift
     */
    public function shiftEndAt(array $shift, CarbonInterface $date): Carbon
    {
        $day = Carbon::parse($date)->copy()->startOfDay();
        $end = $day->copy()->setTimeFromTimeString((string) $shift['end_time']);

        if (($shift['is_overnight'] ?? false) || $end->lte($this->shiftStartAt($shift, $day))) {
            $end->addDay();
        }

        return $end;
    }

    public function durationMinutes(CarbonInterface $start, CarbonInterface $end): int
    {
        return max(0, (int) $start->diffInMinutes($end));
    }

    public function deductBreak(int $grossMinutes, int $breakMinutes): int
    {
        return max(0, $grossMinutes - max(0, $breakMinutes));
    }
}
