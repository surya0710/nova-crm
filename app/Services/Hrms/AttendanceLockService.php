<?php

namespace App\Services\Hrms;

use App\Models\AttendancePeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSnapshot;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceLockService
{
    /** Payroll run statuses that permanently block attendance reopen. */
    public const BLOCKING_PAYROLL_STATUSES = ['approved', 'published', 'reversed', 'paid', 'locked'];

    /** Draft-like statuses that are invalidated on reopen. */
    public const INVALIDATABLE_PAYROLL_STATUSES = ['draft', 'running', 'calculated'];

    public function __construct(
        protected AuditLogger $auditLogger,
        protected AttendanceValidationService $validationService,
        protected AttendanceSnapshotService $snapshotService,
        protected TenantContext $tenantContext,
    ) {}

    public function createPeriod(array $data, User $actor): AttendancePeriod
    {
        return DB::transaction(function () use ($data, $actor): AttendancePeriod {
            $start = Carbon::parse($data['start_date'])->startOfDay();
            $end = Carbon::parse($data['end_date'])->startOfDay();

            if ($end->lt($start)) {
                throw ValidationException::withMessages([
                    'end_date' => __('End date must be on or after start date.'),
                ]);
            }

            $organizationId = $data['organization_id']
                ?? $this->tenantContext->id();

            if ($organizationId === null) {
                throw ValidationException::withMessages([
                    'organization_id' => __('Organization context is required to create an attendance period.'),
                ]);
            }

            $period = AttendancePeriod::query()->create([
                'organization_id' => $organizationId,
                'name' => $data['name'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => AttendancePeriod::STATUS_OPEN,
                'payroll_period_id' => $data['payroll_period_id'] ?? null,
            ]);

            $this->auditLogger->log($period, 'attendance_period_created', [
                'name' => $period->name,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->toDateString(),
            ], $actor);

            return $period;
        });
    }

    public function createPeriodForPayroll(PayrollPeriod $payrollPeriod, User $actor): AttendancePeriod
    {
        $existing = $this->findForPayrollPeriod($payrollPeriod);
        if ($existing !== null) {
            return $existing;
        }

        return $this->createPeriod([
            'organization_id' => $payrollPeriod->organization_id,
            'name' => 'Attendance '.$payrollPeriod->name,
            'start_date' => $payrollPeriod->start_date->toDateString(),
            'end_date' => $payrollPeriod->end_date->toDateString(),
            'payroll_period_id' => $payrollPeriod->id,
        ], $actor);
    }

    public function freeze(AttendancePeriod $period, User $actor): AttendancePeriod
    {
        return DB::transaction(function () use ($period, $actor): AttendancePeriod {
            if (! $period->isOpen()) {
                throw ValidationException::withMessages([
                    'status' => __('Only open attendance periods can be frozen.'),
                ]);
            }

            $validation = $this->validationService->validatePeriod($period);
            if (! $validation['passed']) {
                throw ValidationException::withMessages([
                    'period' => collect($validation['errors'])
                        ->map(fn (array $error) => '['.$error['code'].'] '.$error['message'])
                        ->all() ?: [__('Attendance validation failed before freeze.')],
                ]);
            }

            $period->update([
                'status' => AttendancePeriod::STATUS_FROZEN,
                'frozen_at' => now(),
                'frozen_by' => $actor->id,
            ]);

            $this->auditLogger->log($period, 'attendance_frozen', [
                'attendance_period_id' => $period->id,
                'warnings' => $validation['warnings'],
            ], $actor);

            return $period->fresh();
        });
    }

    public function lock(AttendancePeriod $period, User $actor): AttendancePeriod
    {
        return DB::transaction(function () use ($period, $actor): AttendancePeriod {
            if (! in_array($period->status, [AttendancePeriod::STATUS_OPEN, AttendancePeriod::STATUS_FROZEN], true)) {
                throw ValidationException::withMessages([
                    'status' => __('Only open or frozen attendance periods can be locked.'),
                ]);
            }

            $validation = $this->validationService->validatePeriod($period);
            if (! $validation['passed']) {
                throw ValidationException::withMessages([
                    'period' => collect($validation['errors'])
                        ->map(fn (array $error) => '['.$error['code'].'] '.$error['message'])
                        ->all() ?: [__('Attendance validation failed before lock.')],
                ]);
            }

            $snapshot = $this->snapshotService->generate($period, $actor);

            AttendanceRecord::query()
                ->whereBetween('attendance_date', [
                    $period->start_date->toDateString(),
                    $period->end_date->toDateString(),
                ])
                ->update([
                    'locked_at' => now(),
                    'locked_by' => $actor->id,
                ]);

            $period->update([
                'status' => AttendancePeriod::STATUS_LOCKED,
                'locked_at' => now(),
                'locked_by' => $actor->id,
            ]);

            $this->auditLogger->log($period, 'attendance_locked', [
                'attendance_period_id' => $period->id,
                'snapshot_id' => $snapshot->id,
                'snapshot_version' => $snapshot->snapshot_version,
            ], $actor);

            return $period->fresh(['snapshots']);
        });
    }

    public function reopen(AttendancePeriod $period, User $actor): AttendancePeriod
    {
        return DB::transaction(function () use ($period, $actor): AttendancePeriod {
            if (! $period->isLocked() && ! $period->isFrozen()) {
                throw ValidationException::withMessages([
                    'status' => __('Only frozen or locked attendance periods can be reopened.'),
                ]);
            }

            $this->assertReopenAllowed($period);

            $this->invalidateDraftPayroll($period, $actor);

            AttendanceRecord::query()
                ->whereBetween('attendance_date', [
                    $period->start_date->toDateString(),
                    $period->end_date->toDateString(),
                ])
                ->update([
                    'locked_at' => null,
                    'locked_by' => null,
                ]);

            $period->update([
                'status' => AttendancePeriod::STATUS_OPEN,
                'reopened_at' => now(),
                'reopened_by' => $actor->id,
                'frozen_at' => null,
                'frozen_by' => null,
                'locked_at' => null,
                'locked_by' => null,
            ]);

            $this->auditLogger->log($period, 'attendance_reopened', [
                'attendance_period_id' => $period->id,
            ], $actor);

            return $period->fresh();
        });
    }

    public function findForDate(CarbonInterface $date): ?AttendancePeriod
    {
        $day = Carbon::parse($date)->toDateString();

        return AttendancePeriod::query()
            ->where('start_date', '<=', $day)
            ->where('end_date', '>=', $day)
            ->orderByDesc('id')
            ->first();
    }

    public function findForPayrollPeriod(PayrollPeriod $payrollPeriod): ?AttendancePeriod
    {
        $byLink = AttendancePeriod::query()
            ->where('payroll_period_id', $payrollPeriod->id)
            ->first();

        if ($byLink !== null) {
            return $byLink;
        }

        return AttendancePeriod::query()
            ->whereDate('start_date', $payrollPeriod->start_date->toDateString())
            ->whereDate('end_date', $payrollPeriod->end_date->toDateString())
            ->first();
    }

    public function requireLockedSnapshotForPayroll(PayrollPeriod $payrollPeriod): AttendanceSnapshot
    {
        $period = $this->findForPayrollPeriod($payrollPeriod);

        if ($period === null || ! $period->isLocked()) {
            throw ValidationException::withMessages([
                'attendance' => __('Attendance must be locked with a snapshot before payroll can run.'),
            ]);
        }

        $snapshot = $period->activeSnapshot();
        if ($snapshot === null) {
            throw ValidationException::withMessages([
                'attendance' => __('Attendance snapshot is missing for the locked period.'),
            ]);
        }

        return $snapshot;
    }

    /**
     * Assert whether an attendance mutation is allowed for the given date.
     *
     * @param  bool  $isPrivileged  HR/manager correction path (allowed while frozen)
     */
    public function assertEditable(CarbonInterface $date, bool $isPrivileged = false): void
    {
        $period = $this->findForDate($date);
        if ($period === null) {
            return;
        }

        if ($period->isLocked()) {
            throw ValidationException::withMessages([
                'attendance' => __('Attendance is locked for this period and cannot be modified.'),
            ]);
        }

        if ($period->isFrozen() && ! $isPrivileged) {
            throw ValidationException::withMessages([
                'attendance' => __('Attendance is frozen. Only HR can apply corrections.'),
            ]);
        }
    }

    protected function assertReopenAllowed(AttendancePeriod $period): void
    {
        $payrollPeriodIds = PayrollPeriod::query()
            ->where(function ($query) use ($period) {
                $query->where('id', $period->payroll_period_id)
                    ->orWhere(function ($inner) use ($period) {
                        $inner->whereDate('start_date', $period->start_date->toDateString())
                            ->whereDate('end_date', $period->end_date->toDateString());
                    });
            })
            ->pluck('id');

        if ($payrollPeriodIds->isEmpty()) {
            return;
        }

        $blocking = PayrollRun::query()
            ->whereIn('payroll_period_id', $payrollPeriodIds)
            ->whereIn('status', self::BLOCKING_PAYROLL_STATUSES)
            ->exists();

        if ($blocking) {
            throw ValidationException::withMessages([
                'period' => __('Attendance cannot be reopened because payroll is approved, locked, published, or paid. An explicit rollback workflow is required.'),
            ]);
        }
    }

    protected function invalidateDraftPayroll(AttendancePeriod $period, User $actor): void
    {
        $payrollPeriodIds = PayrollPeriod::query()
            ->where(function ($query) use ($period) {
                $query->where('id', $period->payroll_period_id)
                    ->orWhere(function ($inner) use ($period) {
                        $inner->whereDate('start_date', $period->start_date->toDateString())
                            ->whereDate('end_date', $period->end_date->toDateString());
                    });
            })
            ->pluck('id');

        if ($payrollPeriodIds->isEmpty()) {
            return;
        }

        $runs = PayrollRun::query()
            ->whereIn('payroll_period_id', $payrollPeriodIds)
            ->whereIn('status', self::INVALIDATABLE_PAYROLL_STATUSES)
            ->get();

        foreach ($runs as $run) {
            $previous = $run->status;
            $run->results()->delete();
            $run->validationErrors()->delete();
            $run->update([
                'status' => 'draft',
                'started_at' => null,
                'completed_at' => null,
                'employee_count' => 0,
                'success_count' => 0,
                'error_count' => 0,
            ]);

            $this->auditLogger->log($run, 'payroll_draft_invalidated_by_attendance_reopen', [
                'previous_status' => $previous,
                'attendance_period_id' => $period->id,
            ], $actor);
        }
    }
}
