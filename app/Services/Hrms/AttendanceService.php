<?php

namespace App\Services\Hrms;

use App\Events\AttendanceClockedIn;
use App\Events\AttendanceClockedOut;
use App\Events\AttendanceCorrectionApproved;
use App\Events\AttendanceCorrectionRejected;
use App\Events\AttendanceCorrectionSubmitted;
use App\Events\AttendanceOvertimeRecorded;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\HrmsShift;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected LeaveService $leaveService,
        protected AttendanceCalculationService $calculationService,
        protected AttendanceVersionService $versionService,
        protected AttendanceLockService $lockService,
        protected AttendanceVerificationService $verificationService,
    ) {}

    public function createShift(array $data, User $actor): HrmsShift
    {
        return DB::transaction(function () use ($data, $actor): HrmsShift {
            if (! empty($data['is_default'])) {
                HrmsShift::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $shift = HrmsShift::query()->create($data);
            $this->auditLogger->log($shift, 'shift_created', [
                'name' => $shift->name,
                'code' => $shift->code,
                'is_default' => $shift->is_default,
            ], $actor);

            return $shift;
        });
    }

    public function updateShift(HrmsShift $shift, array $data, User $actor): HrmsShift
    {
        return DB::transaction(function () use ($shift, $data, $actor): HrmsShift {
            if (! empty($data['is_default'])) {
                HrmsShift::query()
                    ->where('is_default', true)
                    ->where('id', '!=', $shift->id)
                    ->update(['is_default' => false]);
            }

            $before = $shift->only(['name', 'code', 'start_time', 'end_time', 'is_active', 'is_default', 'grace_period_minutes']);
            $shift->update($data);
            $this->auditLogger->log($shift, 'shift_updated', [
                'before' => $before,
                'after' => $shift->only(array_keys($before)),
            ], $actor);

            return $shift;
        });
    }

    public function deleteShift(HrmsShift $shift, User $actor): void
    {
        DB::transaction(function () use ($shift, $actor): void {
            $this->auditLogger->log($shift, 'shift_deleted', ['name' => $shift->name], $actor);
            $shift->delete();
        });
    }

    public function assignShift(Employee $employee, array $data, User $actor): EmployeeShiftAssignment
    {
        return DB::transaction(function () use ($employee, $data, $actor): EmployeeShiftAssignment {
            $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();
            $effectiveTo = isset($data['effective_to']) ? Carbon::parse($data['effective_to'])->startOfDay() : null;

            if ($effectiveTo !== null && $effectiveTo->lt($effectiveFrom)) {
                throw ValidationException::withMessages([
                    'effective_to' => __('Effective until must be on or after effective from.'),
                ]);
            }

            $this->assertNoOverlappingAssignment($employee, $effectiveFrom, $effectiveTo);

            $assignment = EmployeeShiftAssignment::query()->create([
                'employee_id' => $employee->id,
                'shift_id' => $data['shift_id'],
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => $effectiveTo?->toDateString(),
            ]);

            $this->auditLogger->log($assignment, 'shift_assigned', [
                'employee_id' => $employee->id,
                'shift_id' => $assignment->shift_id,
                'effective_from' => $assignment->effective_from->toDateString(),
                'effective_to' => $assignment->effective_to?->toDateString(),
            ], $actor);

            return $assignment->load(['employee', 'shift']);
        });
    }

    /**
     * @param  array<string, mixed>  $context  Optional verification context (lat/lng/accuracy/device/biometric).
     */
    public function clockIn(
        Employee $employee,
        ?Carbon $clockInAt,
        User $actor,
        string $source = 'manual',
        array $context = [],
    ): AttendanceRecord {
        $this->assertEmployeeCanClock($employee);

        $clockInAt ??= now();
        $attendanceDate = $clockInAt->copy()->startOfDay();

        // Verify outside the write transaction so failed attempts still leave an audit trail.
        $verification = $this->verificationService->assertVerified(
            $employee,
            'clock_in',
            $context,
            $clockInAt,
            $actor,
        );
        $verificationAttributes = $this->verificationService->attributesForEvent('clock_in', $verification);

        return DB::transaction(function () use ($employee, $clockInAt, $attendanceDate, $actor, $source, $verification, $verificationAttributes): AttendanceRecord {
            $this->lockService->assertEditable($attendanceDate, isPrivileged: false);
            $this->assertCanRecordAttendance($employee, $attendanceDate);

            $existing = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $attendanceDate)
                ->first();

            if ($existing !== null && $existing->clock_in_at !== null) {
                throw ValidationException::withMessages([
                    'employee_id' => __('Employee has already clocked in for this date.'),
                ]);
            }

            $shift = $this->resolveShiftForEmployee($employee, $attendanceDate);

            if ($existing !== null) {
                $nextAttributes = array_merge([
                    'clock_in_at' => $clockInAt,
                    'shift_id' => $shift?->id,
                    'source' => $source,
                    'status' => 'pending',
                ], $verificationAttributes);

                if ($this->versionService->hasMaterialChange($existing, $nextAttributes)) {
                    $this->versionService->archiveAndIncrement($existing, $actor, 'clock_in');
                }

                $existing->update(array_merge($nextAttributes, [
                    'approval_status' => $existing->approval_status ?? 'approved',
                ]));
                $record = $existing->fresh();
            } else {
                $record = AttendanceRecord::query()->create(array_merge([
                    'organization_id' => $employee->organization_id,
                    'employee_id' => $employee->id,
                    'shift_id' => $shift?->id,
                    'attendance_date' => $attendanceDate->toDateString(),
                    'clock_in_at' => $clockInAt,
                    'status' => 'pending',
                    'approval_status' => 'approved',
                    'source' => $source,
                    'version' => 1,
                ], $verificationAttributes));
            }

            $this->verificationService->recordAudit($employee, 'clock_in', $verification, $actor, $record);

            $this->auditLogger->log($record, 'attendance_clocked_in', [
                'employee_id' => $employee->id,
                'clock_in_at' => $record->clock_in_at?->toIso8601String(),
                'source' => $source,
                'verification_status' => $record->clock_in_verification_status,
            ], $actor);

            event(AttendanceClockedIn::forModel($record, ['actor_id' => $actor->id]));

            return $record->load(['employee', 'shift']);
        });
    }

    /**
     * @param  array<string, mixed>  $context  Optional verification context (lat/lng/accuracy/device/biometric).
     */
    public function clockOut(
        Employee $employee,
        ?Carbon $clockOutAt,
        User $actor,
        array $context = [],
    ): AttendanceRecord {
        $this->assertEmployeeCanClock($employee);

        $clockOutAt ??= now();
        $attendanceDate = $clockOutAt->copy()->startOfDay();

        $verification = $this->verificationService->assertVerified(
            $employee,
            'clock_out',
            $context,
            $clockOutAt,
            $actor,
        );
        $verificationAttributes = $this->verificationService->attributesForEvent('clock_out', $verification);

        return DB::transaction(function () use ($employee, $clockOutAt, $attendanceDate, $actor, $verification, $verificationAttributes): AttendanceRecord {
            $this->lockService->assertEditable($attendanceDate, isPrivileged: false);
            $this->assertCanRecordAttendance($employee, $attendanceDate);

            $record = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $attendanceDate)
                ->first();

            if ($record === null || $record->clock_in_at === null) {
                throw ValidationException::withMessages([
                    'employee_id' => __('Employee must clock in before clocking out.'),
                ]);
            }

            if ($record->clock_out_at !== null) {
                throw ValidationException::withMessages([
                    'employee_id' => __('Employee has already clocked out for this date.'),
                ]);
            }

            if ($clockOutAt->lte($record->clock_in_at)) {
                throw ValidationException::withMessages([
                    'clock_out_at' => __('Clock out must be after clock in.'),
                ]);
            }

            $shift = $record->shift ?? $this->resolveShiftForEmployee($employee, $attendanceDate);
            $metricsAttributes = array_merge([
                'clock_out_at' => $clockOutAt,
            ], $verificationAttributes);

            if ($shift !== null) {
                $forMetrics = $record->replicate();
                $forMetrics->clock_out_at = $clockOutAt;
                $metrics = $this->calculateMetrics($forMetrics, $shift);
                $metricsAttributes = array_merge($metricsAttributes, [
                    'shift_id' => $shift->id,
                    'working_minutes' => $metrics['working_minutes'],
                    'break_minutes' => $metrics['break_minutes'],
                    'late_minutes' => $metrics['late_minutes'],
                    'early_departure_minutes' => $metrics['early_departure_minutes'],
                    'overtime_minutes' => $metrics['overtime_minutes'],
                    'status' => $metrics['status'],
                ]);
            } else {
                $grossMinutes = (int) $record->clock_in_at->diffInMinutes($clockOutAt);
                $metricsAttributes = array_merge($metricsAttributes, [
                    'working_minutes' => $grossMinutes,
                    'break_minutes' => 0,
                    'status' => 'present',
                ]);
            }

            $this->versionService->archiveAndIncrement($record, $actor, 'clock_out');
            $record->refresh();
            $record->fill($metricsAttributes)->save();
            $record->refresh();

            $this->verificationService->recordAudit($employee, 'clock_out', $verification, $actor, $record);

            $this->auditLogger->log($record, 'attendance_clocked_out', [
                'employee_id' => $employee->id,
                'clock_out_at' => $record->clock_out_at?->toIso8601String(),
                'working_minutes' => $record->working_minutes,
                'status' => $record->status,
                'version' => $record->version,
                'verification_status' => $record->clock_out_verification_status,
            ], $actor);

            event(AttendanceClockedOut::forModel($record, ['actor_id' => $actor->id]));

            if ($record->overtime_minutes > 0) {
                event(AttendanceOvertimeRecorded::forModel($record, [
                    'actor_id' => $actor->id,
                    'overtime_minutes' => $record->overtime_minutes,
                ]));
            }

            return $record->load(['employee', 'shift']);
        });
    }

    public function submitCorrection(AttendanceRecord $record, array $data, User $actor): AttendanceCorrection
    {
        return DB::transaction(function () use ($record, $data, $actor): AttendanceCorrection {
            $this->lockService->assertEditable(
                $record->attendance_date,
                isPrivileged: $this->actorCanCorrectWhileFrozen($actor)
            );

            $pendingExists = AttendanceCorrection::query()
                ->where('attendance_record_id', $record->id)
                ->where('status', 'pending')
                ->exists();

            if ($pendingExists) {
                throw ValidationException::withMessages([
                    'attendance_record_id' => __('A pending correction already exists for this attendance record.'),
                ]);
            }

            $correction = AttendanceCorrection::query()->create([
                'organization_id' => $record->organization_id,
                'attendance_record_id' => $record->id,
                'employee_id' => $record->employee_id,
                'requested_clock_in_at' => $data['requested_clock_in_at'] ?? null,
                'requested_clock_out_at' => $data['requested_clock_out_at'] ?? null,
                'reason' => $data['reason'],
                'status' => 'pending',
                'target_version' => (int) ($record->version ?? 1),
                'current_step' => 'manager',
                'requires_hr_approval' => (bool) ($data['requires_hr_approval'] ?? false),
            ]);

            $this->auditLogger->log($correction, 'attendance_correction_submitted', [
                'attendance_record_id' => $record->id,
                'employee_id' => $record->employee_id,
                'target_version' => $correction->target_version,
            ], $actor);

            event(AttendanceCorrectionSubmitted::forModel($correction, ['actor_id' => $actor->id]));

            return $correction->load(['attendanceRecord', 'employee']);
        });
    }

    public function approveCorrection(AttendanceCorrection $correction, array $data, User $actor): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $data, $actor): AttendanceCorrection {
            if ($correction->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => __('Only pending corrections can be approved.'),
                ]);
            }

            $record = $correction->attendanceRecord;
            $this->lockService->assertEditable(
                $record->attendance_date,
                isPrivileged: $this->actorCanCorrectWhileFrozen($actor)
            );

            $correction->update([
                'status' => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ]);

            $nextClockIn = $correction->requested_clock_in_at ?? $record->clock_in_at;
            $nextClockOut = $correction->requested_clock_out_at ?? $record->clock_out_at;
            $nextAttributes = [
                'clock_in_at' => $nextClockIn,
                'clock_out_at' => $nextClockOut,
            ];

            if ($nextClockIn !== null && $nextClockOut !== null) {
                $shift = $record->shift ?? $this->resolveShiftForEmployee($record->employee, $record->attendance_date);
                if ($shift !== null) {
                    $temp = $record->replicate();
                    $temp->clock_in_at = $nextClockIn;
                    $temp->clock_out_at = $nextClockOut;
                    $metrics = $this->calculateMetrics($temp, $shift);
                    $nextAttributes = array_merge($nextAttributes, [
                        'shift_id' => $shift->id,
                        'working_minutes' => $metrics['working_minutes'],
                        'break_minutes' => $metrics['break_minutes'],
                        'late_minutes' => $metrics['late_minutes'],
                        'early_departure_minutes' => $metrics['early_departure_minutes'],
                        'overtime_minutes' => $metrics['overtime_minutes'],
                        'status' => $metrics['status'],
                    ]);
                }
            }

            $this->versionService->archiveAndIncrement($record, $actor, 'correction_approved');
            $record->fill($nextAttributes)->save();

            $correction->update([
                'resulting_version' => (int) $record->fresh()->version,
                'current_step' => 'completed',
            ]);

            $this->auditLogger->log($correction, 'attendance_correction_approved', [
                'attendance_record_id' => $record->id,
                'review_notes' => $correction->review_notes,
                'resulting_version' => $correction->resulting_version,
            ], $actor);

            event(AttendanceCorrectionApproved::forModel($correction, ['actor_id' => $actor->id]));

            if ($record->fresh()->overtime_minutes > 0) {
                event(AttendanceOvertimeRecorded::forModel($record->fresh(), [
                    'actor_id' => $actor->id,
                    'overtime_minutes' => $record->fresh()->overtime_minutes,
                    'via_correction' => true,
                ]));
            }

            return $correction->fresh(['attendanceRecord', 'employee', 'reviewer']);
        });
    }

    public function rejectCorrection(AttendanceCorrection $correction, array $data, User $actor): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $data, $actor): AttendanceCorrection {
            if ($correction->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => __('Only pending corrections can be rejected.'),
                ]);
            }

            $this->lockService->assertEditable(
                $correction->attendanceRecord->attendance_date,
                isPrivileged: $this->actorCanCorrectWhileFrozen($actor)
            );

            $correction->update([
                'status' => 'rejected',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
                'current_step' => 'rejected',
            ]);

            $this->auditLogger->log($correction, 'attendance_correction_rejected', [
                'attendance_record_id' => $correction->attendance_record_id,
                'review_notes' => $correction->review_notes,
            ], $actor);

            event(AttendanceCorrectionRejected::forModel($correction, ['actor_id' => $actor->id]));

            return $correction->fresh(['attendanceRecord', 'employee', 'reviewer']);
        });
    }

    /**
     * @return array{
     *     date: string,
     *     total_employees: int,
     *     present: int,
     *     absent: int,
     *     late: int,
     *     on_leave: int,
     *     holiday: int,
     *     weekend: int,
     *     half_day: int,
     *     pending: int,
     *     overtime: int
     * }
     */
    public function dailySummary(?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $dayName = strtolower($date->englishDayOfWeek);

        $totalEmployees = Employee::query()->whereIn('status', config('hrms.clockable_employee_statuses', []))->count();

        $records = AttendanceRecord::query()
            ->whereDate('attendance_date', $date)
            ->get();

        $counts = $records->countBy('status');

        $summary = [
            'date' => $date->toDateString(),
            'total_employees' => $totalEmployees,
            'present' => (int) ($counts->get('present', 0)),
            'absent' => max(0, $totalEmployees - $records->count()),
            'late' => (int) ($counts->get('late', 0)),
            'on_leave' => (int) ($counts->get('on_leave', 0)),
            'holiday' => (int) ($counts->get('holiday', 0)),
            'weekend' => (int) ($counts->get('weekend', 0)),
            'half_day' => (int) ($counts->get('half_day', 0)),
            'pending' => (int) ($counts->get('pending', 0)),
            'overtime' => $records->where('overtime_minutes', '>', 0)->count(),
        ];

        if (in_array($dayName, config('hrms.weekend_days', []), true) && $records->isEmpty()) {
            $summary['weekend'] = $totalEmployees;
            $summary['absent'] = 0;
        }

        return $summary;
    }

    public function resolveShiftForEmployee(Employee $employee, Carbon $date): ?HrmsShift
    {
        $assignment = EmployeeShiftAssignment::query()
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();

        return $assignment?->shift;
    }

    /**
     * @return array{working_minutes: int, break_minutes: int, late_minutes: int, early_departure_minutes: int, overtime_minutes: int, status: string}
     */
    public function calculateMetrics(AttendanceRecord $record, HrmsShift $shift): array
    {
        return $this->calculationService->calculateMetrics($record, $shift);
    }

    /** Attendance reads approved leave — never modifies leave balances. */
    public function isEmployeeOnLeave(Employee $employee, Carbon $date): bool
    {
        return $this->leaveService->getApprovedLeaveForDate($employee, $date)->isNotEmpty();
    }

    /** @return Collection<int, LeaveApplication> */
    public function getApprovedLeaveForDate(Employee $employee, Carbon $date)
    {
        return $this->leaveService->getApprovedLeaveForDate($employee, $date);
    }

    public function isHolidayForEmployee(Employee $employee, Carbon $date): bool
    {
        return $this->calculationService->isHolidayForEmployee($employee, $date);
    }

    public function isWeekend(Carbon $date): bool
    {
        return $this->calculationService->isWeekend($date);
    }

    protected function actorCanCorrectWhileFrozen(User $actor): bool
    {
        return $actor->hasPermission('attendance.correct')
            || $actor->hasPermission('attendance.manage')
            || $actor->hasPermission('attendance.lock');
    }

    protected function assertEmployeeCanClock(Employee $employee): void
    {
        if (! in_array($employee->status, config('hrms.clockable_employee_statuses', []), true)) {
            throw ValidationException::withMessages([
                'employee_id' => __('Employee is not eligible for attendance recording.'),
            ]);
        }
    }

    protected function assertCanRecordAttendance(Employee $employee, Carbon $date): void
    {
        if ($this->isEmployeeOnLeave($employee, $date)) {
            throw ValidationException::withMessages([
                'employee_id' => __('Cannot record attendance while on approved leave.'),
            ]);
        }

        if ($this->isHolidayForEmployee($employee, $date)) {
            throw ValidationException::withMessages([
                'employee_id' => __('Cannot record attendance on a holiday.'),
            ]);
        }

        if ($this->isWeekend($date)) {
            throw ValidationException::withMessages([
                'employee_id' => __('Cannot record attendance on a weekend.'),
            ]);
        }
    }

    protected function assertNoOverlappingAssignment(Employee $employee, Carbon $from, ?Carbon $to): void
    {
        $overlap = EmployeeShiftAssignment::query()
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($from, $to): void {
                $query->where(function ($inner) use ($from, $to): void {
                    $inner->where('effective_from', '<=', ($to ?? $from)->toDateString())
                        ->where(function ($range) use ($from): void {
                            $range->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $from->toDateString());
                        });
                });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' => __('Shift assignment overlaps with an existing assignment.'),
            ]);
        }
    }
}
