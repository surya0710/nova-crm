<?php

namespace App\Services\Hrms;

use App\Events\PayrollAdjustmentApproved;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollAdjustmentService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): PayrollAdjustment
    {
        return DB::transaction(function () use ($data, $actor): PayrollAdjustment {
            $employee = Employee::query()->findOrFail($data['employee_id']);
            $type = $data['adjustment_type'];
            $direction = $data['direction']
                ?? (in_array($type, ['penalty'], true) ? 'deduction' : 'earning');

            if ($type === 'penalty') {
                $direction = 'deduction';
            } elseif (in_array($type, ['bonus', 'incentive', 'arrears'], true)) {
                $direction = 'earning';
            }

            $adjustment = PayrollAdjustment::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'payroll_period_id' => $data['payroll_period_id'] ?? null,
                'adjustment_number' => $this->nextNumber(),
                'adjustment_type' => $type,
                'direction' => $direction,
                'amount' => round((float) $data['amount'], 2),
                'status' => 'draft',
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'effective_date' => $data['effective_date'] ?? now()->toDateString(),
                'created_by' => $actor->id,
            ]);

            $this->auditLogger->log($adjustment, 'payroll_adjustment_created', [
                'employee_id' => $employee->id,
                'adjustment_type' => $type,
                'amount' => $adjustment->amount,
            ], $actor);

            return $adjustment->load(['employee', 'payrollPeriod']);
        });
    }

    public function approve(PayrollAdjustment $adjustment, User $actor): PayrollAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor): PayrollAdjustment {
            $adjustment->refresh();

            if (! $adjustment->isDraft()) {
                throw ValidationException::withMessages([
                    'adjustment' => 'Only draft adjustments can be approved.',
                ]);
            }

            $adjustment->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->auditLogger->log($adjustment, 'payroll_adjustment_approved', [
                'amount' => $adjustment->amount,
                'adjustment_type' => $adjustment->adjustment_type,
            ], $actor);

            event(PayrollAdjustmentApproved::forModel($adjustment->fresh(), [
                'actor_id' => $actor->id,
                'employee_id' => $adjustment->employee_id,
            ]));

            return $adjustment->fresh(['employee', 'payrollPeriod', 'approvedBy']);
        });
    }

    public function reject(PayrollAdjustment $adjustment, User $actor, ?string $reason = null): PayrollAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor, $reason): PayrollAdjustment {
            if (! $adjustment->isDraft()) {
                throw ValidationException::withMessages([
                    'adjustment' => 'Only draft adjustments can be rejected.',
                ]);
            }

            $adjustment->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->auditLogger->log($adjustment, 'payroll_adjustment_rejected', [
                'reason' => $reason,
            ], $actor);

            return $adjustment->fresh();
        });
    }

    /**
     * Approved adjustments eligible for a period/employee (not yet applied).
     *
     * @return Collection<int, PayrollAdjustment>
     */
    public function approvedForEmployee(Employee $employee, PayrollPeriod $period): Collection
    {
        return PayrollAdjustment::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($period) {
                $query->where('payroll_period_id', $period->id)
                    ->orWhere(function ($inner) use ($period) {
                        $inner->whereNull('payroll_period_id')
                            ->where(function ($dates) use ($period) {
                                $dates->whereNull('effective_date')
                                    ->orWhereBetween('effective_date', [
                                        $period->start_date->toDateString(),
                                        $period->end_date->toDateString(),
                                    ]);
                            });
                    });
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, PayrollAdjustment>  $adjustments
     */
    public function markApplied(Collection $adjustments, PayrollRun $run, User $actor): void
    {
        foreach ($adjustments as $adjustment) {
            $adjustment->update([
                'status' => 'applied',
                'payroll_run_id' => $run->id,
                'payroll_period_id' => $run->payroll_period_id,
                'applied_at' => now(),
            ]);

            $this->auditLogger->log($adjustment, 'payroll_adjustment_applied', [
                'payroll_run_id' => $run->id,
                'amount' => $adjustment->amount,
            ], $actor);
        }
    }

    /**
     * Roll back applied adjustments for a recalculated run.
     */
    public function releaseAppliedForRun(PayrollRun $run, User $actor): void
    {
        $applied = PayrollAdjustment::query()
            ->where('payroll_run_id', $run->id)
            ->where('status', 'applied')
            ->get();

        foreach ($applied as $adjustment) {
            $adjustment->update([
                'status' => 'approved',
                'payroll_run_id' => null,
                'applied_at' => null,
            ]);

            $this->auditLogger->log($adjustment, 'payroll_adjustment_released', [
                'payroll_run_id' => $run->id,
            ], $actor);
        }
    }

    protected function nextNumber(): string
    {
        $organizationId = $this->tenantContext->id();
        $seq = PayrollAdjustment::query()->where('organization_id', $organizationId)->count() + 1;

        return sprintf('ADJ-%s-%06d', now()->format('Ym'), $seq);
    }
}
