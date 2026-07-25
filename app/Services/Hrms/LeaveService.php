<?php

namespace App\Services\Hrms;

use App\Events\LeaveApproved;
use App\Events\LeaveBalanceAdjusted;
use App\Events\LeaveCancelled;
use App\Events\LeaveRejected;
use App\Events\LeaveSubmitted;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveApprovalStep;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    // -------------------------------------------------------------------------
    // Leave Types
    // -------------------------------------------------------------------------

    public function createLeaveType(array $data, User $actor): LeaveType
    {
        return DB::transaction(function () use ($data, $actor): LeaveType {
            $leaveType = LeaveType::query()->create($data);
            $this->auditLogger->log($leaveType, 'leave_type_created', [
                'name' => $leaveType->name,
                'code' => $leaveType->code,
            ], $actor);

            return $leaveType;
        });
    }

    public function updateLeaveType(LeaveType $leaveType, array $data, User $actor): LeaveType
    {
        return DB::transaction(function () use ($leaveType, $data, $actor): LeaveType {
            $before = $leaveType->only([
                'name', 'code', 'is_paid', 'requires_approval', 'requires_hr_approval',
                'allow_half_day', 'max_days_per_year', 'allocation_days',
                'carry_forward_allowed', 'negative_balance_allowed', 'max_consecutive_days', 'is_active',
            ]);
            $leaveType->update($data);
            $this->auditLogger->log($leaveType, 'leave_type_updated', [
                'before' => $before,
                'after' => $leaveType->only(array_keys($before)),
            ], $actor);

            return $leaveType;
        });
    }

    public function deleteLeaveType(LeaveType $leaveType, User $actor): void
    {
        DB::transaction(function () use ($leaveType, $actor): void {
            $this->auditLogger->log($leaveType, 'leave_type_deleted', ['name' => $leaveType->name], $actor);
            $leaveType->delete();
        });
    }

    public function seedDefaultLeaveTypes(int $organizationId): void
    {
        foreach (config('hrms.default_leave_types', []) as $defaults) {
            LeaveType::query()->firstOrCreate(
                ['organization_id' => $organizationId, 'code' => $defaults['code']],
                array_merge($defaults, [
                    'organization_id' => $organizationId,
                    'allocation_days' => $defaults['max_days_per_year'] ?? null,
                    'is_active' => true,
                ]),
            );
        }
    }

    // -------------------------------------------------------------------------
    // Holidays
    // -------------------------------------------------------------------------

    public function createHoliday(array $data, User $actor): Holiday
    {
        return DB::transaction(function () use ($data, $actor): Holiday {
            $holiday = Holiday::query()->create($data);
            $this->auditLogger->log($holiday, 'holiday_created', [
                'name' => $holiday->name,
                'holiday_date' => $holiday->holiday_date->toDateString(),
            ], $actor);

            return $holiday;
        });
    }

    public function updateHoliday(Holiday $holiday, array $data, User $actor): Holiday
    {
        return DB::transaction(function () use ($holiday, $data, $actor): Holiday {
            $before = $holiday->only(['name', 'holiday_date', 'branch_id', 'is_optional', 'is_recurring']);
            $holiday->update($data);
            $this->auditLogger->log($holiday, 'holiday_updated', [
                'before' => $before,
                'after' => $holiday->only(array_keys($before)),
            ], $actor);

            return $holiday;
        });
    }

    public function deleteHoliday(Holiday $holiday, User $actor): void
    {
        DB::transaction(function () use ($holiday, $actor): void {
            $this->auditLogger->log($holiday, 'holiday_deleted', ['name' => $holiday->name], $actor);
            $holiday->delete();
        });
    }

    /** @return Collection<int, Holiday> */
    public function getHolidaysForEmployee(Employee $employee, ?int $year = null): Collection
    {
        $year ??= (int) now()->year;

        return Holiday::query()
            ->where('organization_id', $employee->organization_id)
            ->where(function ($query) use ($employee): void {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $employee->branch_id);
            })
            ->whereYear('holiday_date', $year)
            ->orderBy('holiday_date')
            ->get();
    }

    public function isHoliday(Employee $employee, Carbon $date): bool
    {
        return Holiday::query()
            ->where('organization_id', $employee->organization_id)
            ->whereDate('holiday_date', $date->toDateString())
            ->where(function ($query) use ($employee): void {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $employee->branch_id);
            })
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Leave Balances & Ledger
    // -------------------------------------------------------------------------

    public function allocateBalance(Employee $employee, LeaveType $leaveType, int $year, float $days, User $actor, string $transactionType = 'allocation'): LeaveBalance
    {
        return DB::transaction(function () use ($employee, $leaveType, $year, $days, $actor, $transactionType): LeaveBalance {
            $balance = $this->findOrCreateBalance($employee, $leaveType, $year);

            $balanceBefore = (float) $balance->balance;
            $balance->entitled = (float) $balance->entitled + $days;
            $this->recalculateBalance($balance);
            $balance->save();

            $this->recordLedgerTransaction(
                $balance,
                $transactionType,
                $days,
                $balanceBefore,
                (float) $balance->balance,
                __('Balance allocated'),
            );

            $this->auditLogger->log($balance, 'leave_balance_allocated', [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'days' => $days,
            ], $actor);

            return $balance->fresh(['employee', 'leaveType']);
        });
    }

    public function adjustBalance(LeaveBalance $balance, float $quantity, string $remarks, User $actor): LeaveBalance
    {
        return DB::transaction(function () use ($balance, $quantity, $remarks, $actor): LeaveBalance {
            $balanceBefore = (float) $balance->balance;
            $balance->entitled = (float) $balance->entitled + $quantity;
            $this->recalculateBalance($balance);
            $balance->save();

            $this->recordLedgerTransaction(
                $balance,
                'manual_adjustment',
                $quantity,
                $balanceBefore,
                (float) $balance->balance,
                $remarks,
            );

            $this->auditLogger->log($balance, 'leave_balance_adjusted', [
                'quantity' => $quantity,
                'remarks' => $remarks,
            ], $actor);

            event(LeaveBalanceAdjusted::forModel($balance, ['actor_id' => $actor->id]));

            return $balance->fresh(['employee', 'leaveType']);
        });
    }

    // -------------------------------------------------------------------------
    // Leave Applications
    // -------------------------------------------------------------------------

    public function applyLeave(Employee $employee, array $data, User $actor, bool $submit = true): LeaveApplication
    {
        return DB::transaction(function () use ($employee, $data, $actor, $submit): LeaveApplication {
            $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $endDate = Carbon::parse($data['end_date'])->startOfDay();
            $isHalfDay = (bool) ($data['is_half_day'] ?? false);
            $halfDayPeriod = $data['half_day_period'] ?? null;

            $this->assertEmployeeCanApplyLeave($employee, $startDate, $endDate);
            $this->assertValidHalfDay($leaveType, $startDate, $endDate, $isHalfDay, $halfDayPeriod);

            $days = $this->calculateLeaveDays($employee, $startDate, $endDate, $isHalfDay);

            if ($days <= 0) {
                throw ValidationException::withMessages([
                    'start_date' => __('Leave days must be greater than zero.'),
                ]);
            }

            $this->assertNoConsecutiveDaysExceeded($leaveType, $days);
            $this->assertNoOverlappingLeave($employee, $startDate, $endDate);

            $year = (int) $startDate->year;
            $balance = $this->findOrCreateBalance($employee, $leaveType, $year);
            $this->assertSufficientBalance($leaveType, $balance, $days);

            $application = LeaveApplication::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'is_half_day' => $isHalfDay,
                'half_day_period' => $halfDayPeriod,
                'days' => $days,
                'reason' => $data['reason'] ?? null,
                'status' => $submit ? 'pending' : 'draft',
                'submitted_at' => $submit ? now() : null,
            ]);

            if ($submit) {
                $this->createApprovalSteps($application, $employee);
                $this->reservePendingBalance($balance, $application, $days, $actor);
                event(LeaveSubmitted::forModel($application, ['actor_id' => $actor->id]));
            }

            $this->auditLogger->log($application, 'leave_applied', [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'days' => $days,
                'status' => $application->status,
            ], $actor);

            return $application->load(['employee', 'leaveType', 'approvalSteps']);
        });
    }

    public function updateLeave(LeaveApplication $application, array $data, User $actor): LeaveApplication
    {
        return DB::transaction(function () use ($application, $data, $actor): LeaveApplication {
            if (! in_array($application->status, ['draft', 'pending'], true)) {
                throw ValidationException::withMessages([
                    'status' => __('Only draft or pending leave can be edited.'),
                ]);
            }

            $employee = $application->employee;
            $leaveType = LeaveType::query()->findOrFail($data['leave_type_id'] ?? $application->leave_type_id);
            $startDate = Carbon::parse($data['start_date'] ?? $application->start_date)->startOfDay();
            $endDate = Carbon::parse($data['end_date'] ?? $application->end_date)->startOfDay();
            $isHalfDay = (bool) ($data['is_half_day'] ?? $application->is_half_day);
            $halfDayPeriod = $data['half_day_period'] ?? $application->half_day_period;

            $this->assertEmployeeCanApplyLeave($employee, $startDate, $endDate);
            $this->assertValidHalfDay($leaveType, $startDate, $endDate, $isHalfDay, $halfDayPeriod);

            $days = $this->calculateLeaveDays($employee, $startDate, $endDate, $isHalfDay);

            if ($days <= 0) {
                throw ValidationException::withMessages([
                    'start_date' => __('Leave days must be greater than zero.'),
                ]);
            }

            $this->assertNoConsecutiveDaysExceeded($leaveType, $days);
            $this->assertNoOverlappingLeave($employee, $startDate, $endDate, $application->id);

            $year = (int) $startDate->year;
            $balance = $this->findOrCreateBalance($employee, $leaveType, $year);

            if ($application->status === 'pending') {
                $this->releasePendingBalance($balance, $application, (float) $application->days);
            }

            $this->assertSufficientBalance($leaveType, $balance, $days);

            $application->update([
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'is_half_day' => $isHalfDay,
                'half_day_period' => $halfDayPeriod,
                'days' => $days,
                'reason' => $data['reason'] ?? $application->reason,
            ]);

            if ($application->status === 'pending') {
                $this->reservePendingBalance($balance, $application->fresh(), $days, $actor);
            }

            $this->auditLogger->log($application, 'leave_updated', [
                'days' => $days,
            ], $actor);

            return $application->fresh(['employee', 'leaveType', 'approvalSteps']);
        });
    }

    public function withdrawLeave(LeaveApplication $application, User $actor): LeaveApplication
    {
        return DB::transaction(function () use ($application, $actor): LeaveApplication {
            if (! in_array($application->status, ['draft', 'pending'], true)) {
                throw ValidationException::withMessages([
                    'status' => __('Only draft or pending leave can be withdrawn.'),
                ]);
            }

            if ($application->status === 'pending') {
                $balance = $this->findOrCreateBalance(
                    $application->employee,
                    $application->leaveType,
                    (int) $application->start_date->year,
                );
                $this->releasePendingBalance($balance, $application, (float) $application->days);
                $this->markPendingStepsSkipped($application);
            }

            $application->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $this->auditLogger->log($application, 'leave_withdrawn', [], $actor);

            return $application->fresh(['employee', 'leaveType', 'approvalSteps']);
        });
    }

    public function approveLeave(LeaveApplication $application, User $actor, ?string $remarks = null): LeaveApplication
    {
        return DB::transaction(function () use ($application, $actor, $remarks): LeaveApplication {
            if ($application->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => __('Only pending leave can be approved.'),
                ]);
            }

            $step = $this->currentPendingStep($application);
            if ($step === null) {
                throw ValidationException::withMessages([
                    'status' => __('No pending approval step found.'),
                ]);
            }

            $this->assertCanActOnStep($step, $actor);

            $step->update([
                'status' => 'approved',
                'acted_at' => now(),
                'comments' => $remarks,
            ]);

            $this->auditLogger->log($step, 'leave_approval_step_approved', [
                'step_order' => $step->step_order,
                'leave_application_id' => $application->id,
            ], $actor);

            $hasMorePending = $application->approvalSteps()
                ->where('status', 'pending')
                ->exists();

            if (! $hasMorePending) {
                $this->finalizeApproval($application, $actor);
            }

            return $application->fresh(['employee', 'leaveType', 'approvalSteps']);
        });
    }

    public function rejectLeave(LeaveApplication $application, User $actor, ?string $remarks = null): LeaveApplication
    {
        return DB::transaction(function () use ($application, $actor, $remarks): LeaveApplication {
            if ($application->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => __('Only pending leave can be rejected.'),
                ]);
            }

            $step = $this->currentPendingStep($application);
            if ($step === null) {
                throw ValidationException::withMessages([
                    'status' => __('No pending approval step found.'),
                ]);
            }

            $this->assertCanActOnStep($step, $actor);

            $step->update([
                'status' => 'rejected',
                'acted_at' => now(),
                'comments' => $remarks,
            ]);

            $balance = $this->findOrCreateBalance(
                $application->employee,
                $application->leaveType,
                (int) $application->start_date->year,
            );
            $this->releasePendingBalance($balance, $application, (float) $application->days);

            $this->markRemainingStepsSkipped($application, $step->step_order);

            $application->update(['status' => 'rejected']);

            $this->auditLogger->log($application, 'leave_rejected', [
                'remarks' => $remarks,
            ], $actor);

            event(LeaveRejected::forModel($application, ['actor_id' => $actor->id]));

            return $application->fresh(['employee', 'leaveType', 'approvalSteps']);
        });
    }

    public function cancelLeave(LeaveApplication $application, User $actor, ?string $remarks = null): LeaveApplication
    {
        return DB::transaction(function () use ($application, $actor, $remarks): LeaveApplication {
            if ($application->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => __('Only approved leave can be cancelled.'),
                ]);
            }

            $cutoffDays = (int) config('hrms.leave_cancellation_cutoff_days', 0);
            $cutoffDate = $application->start_date->copy()->subDays($cutoffDays);

            if (now()->startOfDay()->gt($cutoffDate)) {
                throw ValidationException::withMessages([
                    'start_date' => __('Leave cannot be cancelled after the cutoff date.'),
                ]);
            }

            $balance = $this->findOrCreateBalance(
                $application->employee,
                $application->leaveType,
                (int) $application->start_date->year,
            );

            $balanceBefore = (float) $balance->balance;
            $balance->used = max(0, (float) $balance->used - (float) $application->days);
            $this->recalculateBalance($balance);
            $balance->save();

            $this->recordLedgerTransaction(
                $balance,
                'leave_cancelled',
                (float) $application->days,
                $balanceBefore,
                (float) $balance->balance,
                $remarks ?? __('Leave cancelled'),
                $application,
            );

            $application->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->auditLogger->log($application, 'leave_cancelled', [
                'remarks' => $remarks,
                'days' => $application->days,
            ], $actor);

            event(LeaveCancelled::forModel($application, ['actor_id' => $actor->id]));

            return $application->fresh(['employee', 'leaveType', 'approvalSteps']);
        });
    }

    // -------------------------------------------------------------------------
    // Attendance / Payroll read contracts
    // -------------------------------------------------------------------------

    /** @return Collection<int, LeaveApplication> */
    public function getApprovedLeaveForDate(Employee $employee, Carbon $date): Collection
    {
        return LeaveApplication::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->with('leaveType')
            ->get();
    }

    /** @return Collection<int, LeaveApplication> */
    public function getApprovedLeaveForDateRange(Employee $employee, Carbon $from, Carbon $to): Collection
    {
        return LeaveApplication::query()
            ->where('organization_id', $employee->organization_id)
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('end_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(function ($inner) use ($from, $to): void {
                        $inner->where('start_date', '<=', $from->toDateString())
                            ->where('end_date', '>=', $to->toDateString());
                    });
            })
            ->with('leaveType')
            ->orderBy('start_date')
            ->get();
    }

    /** @return Collection<int, LeaveBalance> */
    public function getBalancesForEmployee(Employee $employee, ?int $year = null): Collection
    {
        $year ??= (int) now()->year;

        return LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->with(['leaveType', 'transactions'])
            ->get();
    }

    /** @return array<string, int> */
    public function dashboardStats(): array
    {
        $pendingApprovals = LeaveApplication::query()->where('status', 'pending')->count();
        $approvedThisMonth = LeaveApplication::query()
            ->where('status', 'approved')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();
        $onLeaveToday = LeaveApplication::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->count();

        return [
            'pending_approvals' => $pendingApprovals,
            'approved_this_month' => $approvedThisMonth,
            'on_leave_today' => $onLeaveToday,
            'leave_types' => LeaveType::query()->where('is_active', true)->count(),
        ];
    }

    public function calculateLeaveDays(Employee $employee, Carbon $startDate, Carbon $endDate, bool $isHalfDay): float
    {
        if ($isHalfDay) {
            if (! $this->isWorkingDay($startDate)) {
                throw ValidationException::withMessages([
                    'start_date' => __('Half-day leave cannot be applied on a non-working day.'),
                ]);
            }

            if ($this->isHoliday($employee, $startDate)) {
                throw ValidationException::withMessages([
                    'start_date' => __('Half-day leave cannot be applied on a holiday.'),
                ]);
            }

            return 0.5;
        }

        $days = 0.0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($this->isWorkingDay($current) && ! $this->isHoliday($employee, $current)) {
                $days += 1;
            }
            $current->addDay();
        }

        return $days;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    public function findOrCreateBalance(Employee $employee, LeaveType $leaveType, int $year): LeaveBalance
    {
        return LeaveBalance::query()->firstOrCreate(
            [
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'entitled' => 0,
                'used' => 0,
                'pending' => 0,
                'balance' => 0,
            ],
        );
    }

    protected function recalculateBalance(LeaveBalance $balance): void
    {
        $balance->balance = round((float) $balance->entitled - (float) $balance->used - (float) $balance->pending, 2);
    }

    protected function recordLedgerTransaction(
        LeaveBalance $balance,
        string $transactionType,
        float $quantity,
        float $balanceBefore,
        float $balanceAfter,
        ?string $remarks = null,
        ?LeaveApplication $reference = null,
    ): LeaveBalanceTransaction {
        return LeaveBalanceTransaction::query()->create([
            'organization_id' => $balance->organization_id,
            'leave_balance_id' => $balance->id,
            'transaction_type' => $transactionType,
            'quantity' => $quantity,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'remarks' => $remarks,
            'reference_type' => $reference !== null ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
        ]);
    }

    protected function reservePendingBalance(LeaveBalance $balance, LeaveApplication $application, float $days, User $actor): void
    {
        $balanceBefore = (float) $balance->balance;
        $balance->pending = (float) $balance->pending + $days;
        $this->recalculateBalance($balance);
        $balance->save();

        $this->recordLedgerTransaction(
            $balance,
            'leave_submitted',
            $days,
            $balanceBefore,
            (float) $balance->balance,
            __('Leave submitted'),
            $application,
        );
    }

    protected function releasePendingBalance(LeaveBalance $balance, LeaveApplication $application, float $days): void
    {
        $balanceBefore = (float) $balance->balance;
        $balance->pending = max(0, (float) $balance->pending - $days);
        $this->recalculateBalance($balance);
        $balance->save();

        $this->recordLedgerTransaction(
            $balance,
            'leave_rejected',
            $days,
            $balanceBefore,
            (float) $balance->balance,
            __('Pending leave released'),
            $application,
        );
    }

    protected function finalizeApproval(LeaveApplication $application, User $actor): void
    {
        $balance = $this->findOrCreateBalance(
            $application->employee,
            $application->leaveType,
            (int) $application->start_date->year,
        );

        $balanceBefore = (float) $balance->balance;
        $days = (float) $application->days;
        $balance->pending = max(0, (float) $balance->pending - $days);
        $balance->used = (float) $balance->used + $days;
        $this->recalculateBalance($balance);
        $balance->save();

        $this->recordLedgerTransaction(
            $balance,
            'leave_approved',
            $days,
            $balanceBefore,
            (float) $balance->balance,
            __('Leave approved'),
            $application,
        );

        $application->update(['status' => 'approved']);

        $this->auditLogger->log($application, 'leave_approved', [
            'days' => $days,
        ], $actor);

        event(LeaveApproved::forModel($application, ['actor_id' => $actor->id]));
    }

    protected function createApprovalSteps(LeaveApplication $application, Employee $employee): void
    {
        $steps = [];
        $order = 1;

        $manager = $employee->reportingManager;
        $steps[] = [
            'organization_id' => $application->organization_id,
            'leave_application_id' => $application->id,
            'step_order' => $order++,
            'approver_employee_id' => $manager?->id,
            'approver_user_id' => $manager?->user_id,
            'status' => 'pending',
        ];

        if ($application->leaveType->requires_hr_approval) {
            $steps[] = [
                'organization_id' => $application->organization_id,
                'leave_application_id' => $application->id,
                'step_order' => $order,
                'approver_employee_id' => null,
                'approver_user_id' => null,
                'status' => 'pending',
            ];
        }

        foreach ($steps as $step) {
            LeaveApprovalStep::query()->create($step);
        }
    }

    protected function currentPendingStep(LeaveApplication $application): ?LeaveApprovalStep
    {
        return $application->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();
    }

    protected function assertCanActOnStep(LeaveApprovalStep $step, User $actor): void
    {
        if ($step->approver_user_id !== null && (int) $step->approver_user_id === (int) $actor->id) {
            return;
        }

        if ($step->approver_employee_id === null && $step->approver_user_id === null) {
            if ($actor->hasPermission('leave.manage', $step->organization)) {
                return;
            }
        }

        if ($step->approver_employee_id !== null) {
            $approverEmployee = Employee::query()->find($step->approver_employee_id);
            if ($approverEmployee !== null && (int) $approverEmployee->user_id === (int) $actor->id) {
                return;
            }
        }

        if ($actor->hasPermission('leave.manage', $step->organization)) {
            return;
        }

        throw ValidationException::withMessages([
            'approver' => __('You are not authorized to act on this approval step.'),
        ]);
    }

    protected function assertEmployeeCanApplyLeave(Employee $employee, Carbon $startDate, Carbon $endDate): void
    {
        if (! in_array($employee->status, config('hrms.leave_applicable_employee_statuses', []), true)) {
            throw ValidationException::withMessages([
                'employee_id' => __('Employee is not eligible to apply for leave.'),
            ]);
        }

        if ($employee->joining_date !== null && $startDate->lt($employee->joining_date)) {
            throw ValidationException::withMessages([
                'start_date' => __('Leave cannot start before employee joining date.'),
            ]);
        }

        if ($employee->exit_date !== null && $endDate->gt($employee->exit_date)) {
            throw ValidationException::withMessages([
                'end_date' => __('Leave cannot extend beyond employee exit date.'),
            ]);
        }
    }

    protected function assertValidHalfDay(
        LeaveType $leaveType,
        Carbon $startDate,
        Carbon $endDate,
        bool $isHalfDay,
        ?string $halfDayPeriod,
    ): void {
        if (! $isHalfDay) {
            return;
        }

        if (! $leaveType->allow_half_day) {
            throw ValidationException::withMessages([
                'is_half_day' => __('Half-day leave is not allowed for this leave type.'),
            ]);
        }

        if (! $startDate->isSameDay($endDate)) {
            throw ValidationException::withMessages([
                'is_half_day' => __('Half-day leave must be for a single day.'),
            ]);
        }

        if ($halfDayPeriod === null || ! array_key_exists($halfDayPeriod, config('hrms.half_day_periods', []))) {
            throw ValidationException::withMessages([
                'half_day_period' => __('A valid half-day period is required.'),
            ]);
        }
    }

    protected function assertNoConsecutiveDaysExceeded(LeaveType $leaveType, float $days): void
    {
        if ($leaveType->max_consecutive_days !== null && $days > $leaveType->max_consecutive_days) {
            throw ValidationException::withMessages([
                'end_date' => __('Leave exceeds the maximum consecutive days allowed for this leave type.'),
            ]);
        }
    }

    protected function assertNoOverlappingLeave(
        Employee $employee,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeApplicationId = null,
    ): void {
        $overlap = LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->when($excludeApplicationId !== null, fn ($q) => $q->where('id', '!=', $excludeApplicationId))
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($inner) use ($startDate, $endDate): void {
                        $inner->where('start_date', '<=', $startDate->toDateString())
                            ->where('end_date', '>=', $endDate->toDateString());
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => __('Leave dates overlap with an existing application.'),
            ]);
        }
    }

    protected function assertSufficientBalance(LeaveType $leaveType, LeaveBalance $balance, float $days): void
    {
        if ($leaveType->negative_balance_allowed) {
            return;
        }

        $available = (float) $balance->balance;

        if ($available < $days) {
            throw ValidationException::withMessages([
                'leave_type_id' => __('Insufficient leave balance.'),
            ]);
        }
    }

    protected function isWorkingDay(Carbon $date): bool
    {
        $dayName = strtolower($date->englishDayOfWeek);
        $organization = app(\App\Services\TenantContext::class)->get();
        $workingDays = $organization?->settings['working_days'] ?? config('hrms.working_days', []);

        return in_array($dayName, $workingDays, true);
    }

    protected function markPendingStepsSkipped(LeaveApplication $application): void
    {
        $application->approvalSteps()
            ->where('status', 'pending')
            ->update(['status' => 'skipped', 'acted_at' => now()]);
    }

    protected function markRemainingStepsSkipped(LeaveApplication $application, int $afterStepOrder): void
    {
        $application->approvalSteps()
            ->where('status', 'pending')
            ->where('step_order', '>', $afterStepOrder)
            ->update(['status' => 'skipped', 'acted_at' => now()]);
    }
}
