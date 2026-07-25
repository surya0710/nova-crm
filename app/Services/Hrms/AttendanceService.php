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

    public function clockIn(Employee $employee, ?Carbon $clockInAt, User $actor, string $source = 'manual'): AttendanceRecord
    {
        return DB::transaction(function () use ($employee, $clockInAt, $actor, $source): AttendanceRecord {
            $this->assertEmployeeCanClock($employee);

            $clockInAt ??= now();
            $attendanceDate = $clockInAt->copy()->startOfDay();

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
                $existing->update([
                    'clock_in_at' => $clockInAt,
                    'shift_id' => $shift?->id,
                    'source' => $source,
                    'status' => 'pending',
                ]);
                $record = $existing->fresh();
            } else {
                $record = AttendanceRecord::query()->create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shift?->id,
                    'attendance_date' => $attendanceDate->toDateString(),
                    'clock_in_at' => $clockInAt,
                    'status' => 'pending',
                    'source' => $source,
                ]);
            }

            $this->auditLogger->log($record, 'attendance_clocked_in', [
                'employee_id' => $employee->id,
                'clock_in_at' => $record->clock_in_at?->toIso8601String(),
                'source' => $source,
            ], $actor);

            event(AttendanceClockedIn::forModel($record, ['actor_id' => $actor->id]));

            return $record->load(['employee', 'shift']);
        });
    }

    public function clockOut(Employee $employee, ?Carbon $clockOutAt, User $actor): AttendanceRecord
    {
        return DB::transaction(function () use ($employee, $clockOutAt, $actor): AttendanceRecord {
            $this->assertEmployeeCanClock($employee);

            $clockOutAt ??= now();
            $attendanceDate = $clockOutAt->copy()->startOfDay();

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

            $record->clock_out_at = $clockOutAt;
            $shift = $record->shift ?? $this->resolveShiftForEmployee($employee, $attendanceDate);

            if ($shift !== null) {
                $metrics = $this->calculateMetrics($record, $shift);
                $record->fill([
                    'shift_id' => $shift->id,
                    'working_minutes' => $metrics['working_minutes'],
                    'late_minutes' => $metrics['late_minutes'],
                    'early_departure_minutes' => $metrics['early_departure_minutes'],
                    'overtime_minutes' => $metrics['overtime_minutes'],
                    'status' => $metrics['status'],
                ]);
            } else {
                $grossMinutes = (int) $record->clock_in_at->diffInMinutes($clockOutAt);
                $record->fill([
                    'working_minutes' => $grossMinutes,
                    'status' => 'present',
                ]);
            }

            $record->save();
            $record->refresh();

            $this->auditLogger->log($record, 'attendance_clocked_out', [
                'employee_id' => $employee->id,
                'clock_out_at' => $record->clock_out_at?->toIso8601String(),
                'working_minutes' => $record->working_minutes,
                'status' => $record->status,
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
                'attendance_record_id' => $record->id,
                'employee_id' => $record->employee_id,
                'requested_clock_in_at' => $data['requested_clock_in_at'] ?? null,
                'requested_clock_out_at' => $data['requested_clock_out_at'] ?? null,
                'reason' => $data['reason'],
                'status' => 'pending',
            ]);

            $this->auditLogger->log($correction, 'attendance_correction_submitted', [
                'attendance_record_id' => $record->id,
                'employee_id' => $record->employee_id,
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

            $correction->update([
                'status' => 'approved',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ]);

            $record = $correction->attendanceRecord;
            $record->clock_in_at = $correction->requested_clock_in_at ?? $record->clock_in_at;
            $record->clock_out_at = $correction->requested_clock_out_at ?? $record->clock_out_at;

            if ($record->clock_in_at !== null && $record->clock_out_at !== null) {
                $shift = $record->shift ?? $this->resolveShiftForEmployee($record->employee, $record->attendance_date);
                if ($shift !== null) {
                    $metrics = $this->calculateMetrics($record, $shift);
                    $record->fill([
                        'shift_id' => $shift->id,
                        'working_minutes' => $metrics['working_minutes'],
                        'late_minutes' => $metrics['late_minutes'],
                        'early_departure_minutes' => $metrics['early_departure_minutes'],
                        'overtime_minutes' => $metrics['overtime_minutes'],
                        'status' => $metrics['status'],
                    ]);
                }
            }

            $record->save();

            $this->auditLogger->log($correction, 'attendance_correction_approved', [
                'attendance_record_id' => $record->id,
                'review_notes' => $correction->review_notes,
            ], $actor);

            event(AttendanceCorrectionApproved::forModel($correction, ['actor_id' => $actor->id]));

            if ($record->overtime_minutes > 0) {
                event(AttendanceOvertimeRecorded::forModel($record, [
                    'actor_id' => $actor->id,
                    'overtime_minutes' => $record->overtime_minutes,
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

            $correction->update([
                'status' => 'rejected',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
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
     * @return array{working_minutes: int, late_minutes: int, early_departure_minutes: int, overtime_minutes: int, status: string}
     */
    public function calculateMetrics(AttendanceRecord $record, HrmsShift $shift): array
    {
        $clockIn = $record->clock_in_at;
        $clockOut = $record->clock_out_at;

        if ($clockIn === null || $clockOut === null) {
            return [
                'working_minutes' => 0,
                'late_minutes' => 0,
                'early_departure_minutes' => 0,
                'overtime_minutes' => 0,
                'status' => $record->status ?? 'pending',
            ];
        }

        $attendanceDate = $record->attendance_date->copy()->startOfDay();
        $shiftStart = $this->shiftStartAt($shift, $attendanceDate);
        $shiftEnd = $this->shiftEndAt($shift, $attendanceDate);
        $graceEnd = $shiftStart->copy()->addMinutes((int) $shift->grace_period_minutes);

        $lateMinutes = $clockIn->gt($graceEnd) ? (int) $graceEnd->diffInMinutes($clockIn) : 0;
        $earlyDepartureMinutes = $clockOut->lt($shiftEnd) ? (int) $clockOut->diffInMinutes($shiftEnd) : 0;

        $grossMinutes = (int) $clockIn->diffInMinutes($clockOut);
        $workingMinutes = max(0, $grossMinutes - (int) $shift->break_minutes);

        $overtimeThreshold = $shift->overtime_threshold_minutes
            ?? (int) round(((float) $shift->working_hours) * 60);
        $overtimeMinutes = max(0, $workingMinutes - $overtimeThreshold);

        $minimumWorking = $shift->minimum_working_minutes
            ?? (int) round(((float) $shift->working_hours) * 60);

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
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'status' => $status,
        ];
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

    protected function assertEmployeeCanClock(Employee $employee): void
    {
        if (! in_array($employee->status, config('hrms.clockable_employee_statuses', []), true)) {
            throw ValidationException::withMessages([
                'employee_id' => __('Employee is not eligible for attendance recording.'),
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
