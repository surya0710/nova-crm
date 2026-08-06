<?php

namespace App\Services\Hrms;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceOvertimeEntry;
use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceValidationService
{
    public const ERROR_MISSING_CHECKOUT = 'missing_checkout';

    public const ERROR_PENDING_CORRECTION = 'pending_correction';

    public const ERROR_PENDING_APPROVAL = 'pending_approval';

    public const ERROR_MISSING_SHIFT = 'missing_shift';

    public const ERROR_INVALID_HOURS = 'invalid_hours';

    public const ERROR_DUPLICATE_ATTENDANCE = 'duplicate_attendance';

    public const ERROR_UNAPPROVED_OVERTIME = 'unapproved_overtime';

    public const WARNING_LONG_WORKING_HOURS = 'long_working_hours';

    public const WARNING_MISSING_BREAK = 'missing_break';

    /**
     * @return array{passed: bool, errors: list<array{code: string, message: string, attendance_record_id?: int|null, employee_id?: int|null}>, warnings: list<array{code: string, message: string, attendance_record_id?: int|null, employee_id?: int|null}>}
     */
    public function validatePeriod(AttendancePeriod $period): array
    {
        $records = AttendanceRecord::query()
            ->whereBetween('attendance_date', [
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
            ])
            ->with(['employee', 'shift'])
            ->get();

        $errors = [];
        $warnings = [];

        foreach ($records as $record) {
            $result = $this->validateRecord($record);
            $errors = array_merge($errors, $result['errors']);
            $warnings = array_merge($warnings, $result['warnings']);
        }

        $duplicates = $this->findDuplicateGroups($records);
        foreach ($duplicates as $group) {
            /** @var AttendanceRecord $record */
            $record = $group->first();
            $errors[] = [
                'code' => self::ERROR_DUPLICATE_ATTENDANCE,
                'message' => __('Duplicate attendance detected for employee on :date.', [
                    'date' => $record->attendance_date?->toDateString(),
                ]),
                'attendance_record_id' => $record->id,
                'employee_id' => $record->employee_id,
            ];
        }

        $pendingOvertime = AttendanceOvertimeEntry::query()
            ->whereBetween('attendance_date', [
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
            ])
            ->where('status', AttendanceOvertimeEntry::STATUS_PENDING)
            ->get();

        foreach ($pendingOvertime as $entry) {
            $errors[] = [
                'code' => self::ERROR_UNAPPROVED_OVERTIME,
                'message' => __('Unapproved overtime exists for employee on :date.', [
                    'date' => Carbon::parse($entry->attendance_date)->toDateString(),
                ]),
                'attendance_record_id' => $entry->attendance_record_id,
                'employee_id' => $entry->employee_id,
            ];
        }

        return [
            'passed' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array{errors: list<array{code: string, message: string, attendance_record_id?: int|null, employee_id?: int|null}>, warnings: list<array{code: string, message: string, attendance_record_id?: int|null, employee_id?: int|null}>}
     */
    public function validateRecord(AttendanceRecord $record): array
    {
        $errors = [];
        $warnings = [];
        $maxWorking = (int) config('hrms.attendance_validation.long_working_minutes', 720);

        if ($record->clock_in_at !== null && $record->clock_out_at === null) {
            $errors[] = $this->issue(
                self::ERROR_MISSING_CHECKOUT,
                __('Missing checkout for attendance on :date.', [
                    'date' => $record->attendance_date?->toDateString(),
                ]),
                $record
            );
        }

        $pendingCorrection = AttendanceCorrection::query()
            ->where('attendance_record_id', $record->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingCorrection) {
            $errors[] = $this->issue(
                self::ERROR_PENDING_CORRECTION,
                __('Pending correction exists for attendance on :date.', [
                    'date' => $record->attendance_date?->toDateString(),
                ]),
                $record
            );
        }

        if (($record->approval_status ?? 'approved') === 'pending') {
            $errors[] = $this->issue(
                self::ERROR_PENDING_APPROVAL,
                __('Attendance approval is still pending for :date.', [
                    'date' => $record->attendance_date?->toDateString(),
                ]),
                $record
            );
        }

        if ($record->clock_in_at !== null && $record->shift_id === null) {
            $errors[] = $this->issue(
                self::ERROR_MISSING_SHIFT,
                __('Missing shift for attendance on :date.', [
                    'date' => $record->attendance_date?->toDateString(),
                ]),
                $record
            );
        }

        if (
            $record->clock_in_at !== null
            && $record->clock_out_at !== null
            && $record->clock_out_at->lte($record->clock_in_at)
        ) {
            $errors[] = $this->issue(
                self::ERROR_INVALID_HOURS,
                __('Invalid working hours for attendance on :date.', [
                    'date' => $record->attendance_date?->toDateString(),
                ]),
                $record
            );
        }

        if ((int) ($record->working_minutes ?? 0) > $maxWorking) {
            $warnings[] = $this->issue(
                self::WARNING_LONG_WORKING_HOURS,
                __('Long working hours detected for attendance on :date.', [
                    'date' => $record->attendance_date?->toDateString(),
                ]),
                $record
            );
        }

        $expectedBreak = (int) ($record->shift?->break_minutes ?? 0);
        if (
            $expectedBreak > 0
            && $record->clock_out_at !== null
            && (int) ($record->break_minutes ?? 0) === 0
            && (int) ($record->working_minutes ?? 0) > 0
        ) {
            $warnings[] = $this->issue(
                self::WARNING_MISSING_BREAK,
                __('Break minutes missing for attendance on :date.', [
                    'date' => $record->attendance_date?->toDateString(),
                ]),
                $record
            );
        }

        return compact('errors', 'warnings');
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $records
     * @return Collection<int, Collection<int, AttendanceRecord>>
     */
    protected function findDuplicateGroups(Collection $records): Collection
    {
        return $records
            ->groupBy(fn (AttendanceRecord $record) => $record->employee_id.'|'.$record->attendance_date?->toDateString())
            ->filter(fn (Collection $group) => $group->count() > 1);
    }

    /**
     * @return array{code: string, message: string, attendance_record_id: int|null, employee_id: int|null}
     */
    protected function issue(string $code, string $message, AttendanceRecord $record): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'attendance_record_id' => $record->id,
            'employee_id' => $record->employee_id,
        ];
    }
}
