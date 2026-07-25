<?php

namespace App\Services\Hrms;

use App\Contracts\Payroll\PayrollCalculationContract;
use App\Events\EmployeeSalaryAssigned;
use App\Events\PayrollPeriodCreated;
use App\Events\PayrollPeriodLocked;
use App\Events\SalaryStructureCreated;
use App\Events\SalaryStructureUpdated;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use App\Models\EmployeeSalaryAssignment;
use App\Models\PayrollConfiguration;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\SalaryStructureComponent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService implements PayrollCalculationContract
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected LeaveService $leaveService,
    ) {}

    // -------------------------------------------------------------------------
    // Salary Components
    // -------------------------------------------------------------------------

    public function createSalaryComponent(array $data, User $actor): SalaryComponent
    {
        return DB::transaction(function () use ($data, $actor): SalaryComponent {
            $component = SalaryComponent::query()->create($this->normalizeComponentFlags($data));
            $this->auditLogger->log($component, 'salary_component_created', [
                'name' => $component->name,
                'code' => $component->code,
                'component_type' => $component->component_type,
            ], $actor);

            return $component;
        });
    }

    public function updateSalaryComponent(SalaryComponent $component, array $data, User $actor): SalaryComponent
    {
        return DB::transaction(function () use ($component, $data, $actor): SalaryComponent {
            $before = $component->only([
                'name', 'code', 'component_type', 'is_taxable', 'is_recurring',
                'formula_supported', 'is_active', 'description',
            ]);
            $component->update($this->normalizeComponentFlags($data));
            $this->auditLogger->log($component, 'salary_component_updated', [
                'before' => $before,
                'after' => $component->only(array_keys($before)),
            ], $actor);

            return $component->fresh();
        });
    }

    public function deleteSalaryComponent(SalaryComponent $component, User $actor): void
    {
        DB::transaction(function () use ($component, $actor): void {
            if ($component->structureComponents()->exists()) {
                throw ValidationException::withMessages([
                    'component' => 'Cannot delete a salary component that is attached to a salary structure.',
                ]);
            }

            $this->auditLogger->log($component, 'salary_component_deleted', [
                'name' => $component->name,
                'code' => $component->code,
            ], $actor);
            $component->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Salary Structures
    // -------------------------------------------------------------------------

    public function createSalaryStructure(array $data, User $actor): SalaryStructure
    {
        return DB::transaction(function () use ($data, $actor): SalaryStructure {
            $components = $data['components'] ?? [];
            unset($data['components']);

            $structure = SalaryStructure::query()->create([
                ...$data,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            ]);

            $this->syncStructureComponents($structure, $components);

            $this->auditLogger->log($structure, 'salary_structure_created', [
                'name' => $structure->name,
                'effective_date' => $structure->effective_date?->toDateString(),
                'component_count' => count($components),
            ], $actor);

            event(SalaryStructureCreated::forModel($structure, ['actor_id' => $actor->id]));

            return $structure->load('structureComponents.salaryComponent');
        });
    }

    public function updateSalaryStructure(SalaryStructure $structure, array $data, User $actor): SalaryStructure
    {
        return DB::transaction(function () use ($structure, $data, $actor): SalaryStructure {
            $components = $data['components'] ?? null;
            unset($data['components']);

            $before = $structure->only(['name', 'description', 'effective_date', 'is_active']);
            $structure->update($data);

            if (is_array($components)) {
                $structure->structureComponents()->delete();
                $this->syncStructureComponents($structure, $components);
            }

            $this->auditLogger->log($structure, 'salary_structure_updated', [
                'before' => $before,
                'after' => $structure->only(array_keys($before)),
            ], $actor);

            event(SalaryStructureUpdated::forModel($structure, ['actor_id' => $actor->id]));

            return $structure->fresh('structureComponents.salaryComponent');
        });
    }

    public function deleteSalaryStructure(SalaryStructure $structure, User $actor): void
    {
        DB::transaction(function () use ($structure, $actor): void {
            if ($structure->salaryAssignments()->exists()) {
                throw ValidationException::withMessages([
                    'structure' => 'Cannot delete a salary structure that has employee assignments.',
                ]);
            }

            $this->auditLogger->log($structure, 'salary_structure_deleted', [
                'name' => $structure->name,
            ], $actor);
            $structure->structureComponents()->delete();
            $structure->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Employee Salary Assignments
    // -------------------------------------------------------------------------

    public function assignSalaryStructure(Employee $employee, array $data, User $actor): EmployeeSalaryAssignment
    {
        return DB::transaction(function () use ($employee, $data, $actor): EmployeeSalaryAssignment {
            $structure = SalaryStructure::query()->findOrFail($data['salary_structure_id']);
            $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();

            if ($structure->organization_id !== $employee->organization_id) {
                throw ValidationException::withMessages([
                    'salary_structure_id' => 'Salary structure must belong to the same organization.',
                ]);
            }

            if (! $structure->is_active) {
                throw ValidationException::withMessages([
                    'salary_structure_id' => 'Cannot assign an inactive salary structure.',
                ]);
            }

            $overlapping = EmployeeSalaryAssignment::query()
                ->where('employee_id', $employee->id)
                ->where('effective_from', '<=', $effectiveFrom->toDateString())
                ->where(function ($query) use ($effectiveFrom) {
                    $query->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', $effectiveFrom->toDateString());
                })
                ->exists();

            // Close open historical assignments — never overwrite rows.
            $openAssignments = EmployeeSalaryAssignment::query()
                ->where('employee_id', $employee->id)
                ->whereNull('effective_until')
                ->where('effective_from', '<', $effectiveFrom->toDateString())
                ->get();

            foreach ($openAssignments as $open) {
                $until = $effectiveFrom->copy()->subDay();
                if ($until->lt($open->effective_from)) {
                    throw ValidationException::withMessages([
                        'effective_from' => 'Effective from must be after the current assignment start date.',
                    ]);
                }
                $open->update(['effective_until' => $until->toDateString()]);
            }

            $sameDay = EmployeeSalaryAssignment::query()
                ->where('employee_id', $employee->id)
                ->whereDate('effective_from', $effectiveFrom->toDateString())
                ->exists();

            if ($sameDay || ($overlapping && $openAssignments->isEmpty())) {
                $stillOverlapping = EmployeeSalaryAssignment::query()
                    ->where('employee_id', $employee->id)
                    ->where('effective_from', '<=', $effectiveFrom->toDateString())
                    ->where(function ($query) use ($effectiveFrom) {
                        $query->whereNull('effective_until')
                            ->orWhere('effective_until', '>=', $effectiveFrom->toDateString());
                    })
                    ->exists();

                if ($stillOverlapping) {
                    throw ValidationException::withMessages([
                        'effective_from' => 'An overlapping salary assignment already exists for this date. Historical assignments cannot be overwritten.',
                    ]);
                }
            }

            $assignment = EmployeeSalaryAssignment::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'salary_structure_id' => $structure->id,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_until' => $data['effective_until'] ?? null,
                'annual_ctc' => $data['annual_ctc'] ?? null,
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $actor->id,
            ]);

            $this->auditLogger->log($assignment, 'employee_salary_assigned', [
                'employee_id' => $employee->id,
                'salary_structure_id' => $structure->id,
                'effective_from' => $assignment->effective_from->toDateString(),
                'annual_ctc' => $assignment->annual_ctc,
            ], $actor);

            event(EmployeeSalaryAssigned::forModel($assignment, [
                'actor_id' => $actor->id,
                'employee_id' => $employee->id,
            ]));

            return $assignment->load(['salaryStructure', 'employee']);
        });
    }

    // -------------------------------------------------------------------------
    // Payroll Periods
    // -------------------------------------------------------------------------

    public function createPayrollPeriod(array $data, User $actor): PayrollPeriod
    {
        return DB::transaction(function () use ($data, $actor): PayrollPeriod {
            $start = Carbon::parse($data['start_date'])->startOfDay();
            $end = Carbon::parse($data['end_date'])->startOfDay();

            if ($end->lt($start)) {
                throw ValidationException::withMessages([
                    'end_date' => 'End date must be on or after the start date.',
                ]);
            }

            $period = PayrollPeriod::query()->create([
                'name' => $data['name'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => $data['status'] ?? 'draft',
            ]);

            $this->auditLogger->log($period, 'payroll_period_created', [
                'name' => $period->name,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->toDateString(),
                'status' => $period->status,
            ], $actor);

            event(PayrollPeriodCreated::forModel($period, ['actor_id' => $actor->id]));

            return $period;
        });
    }

    public function updatePayrollPeriod(PayrollPeriod $period, array $data, User $actor): PayrollPeriod
    {
        return DB::transaction(function () use ($period, $data, $actor): PayrollPeriod {
            if ($period->isLocked()) {
                throw ValidationException::withMessages([
                    'period' => 'Locked or processed payroll periods cannot be updated.',
                ]);
            }

            $before = $period->only(['name', 'start_date', 'end_date', 'status']);
            $period->update($data);
            $this->auditLogger->log($period, 'payroll_period_updated', [
                'before' => $before,
                'after' => $period->only(array_keys($before)),
            ], $actor);

            return $period->fresh();
        });
    }

    public function lockPayrollPeriod(PayrollPeriod $period, User $actor): PayrollPeriod
    {
        return DB::transaction(function () use ($period, $actor): PayrollPeriod {
            if ($period->status === 'locked') {
                throw ValidationException::withMessages([
                    'period' => 'Payroll period is already locked.',
                ]);
            }

            if ($period->status === 'processed') {
                throw ValidationException::withMessages([
                    'period' => 'Processed payroll periods cannot be locked again.',
                ]);
            }

            $before = $period->status;
            $period->update(['status' => 'locked']);

            $this->auditLogger->log($period, 'payroll_period_locked', [
                'before_status' => $before,
                'after_status' => 'locked',
            ], $actor);

            event(PayrollPeriodLocked::forModel($period, ['actor_id' => $actor->id]));

            return $period->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Payroll Configuration
    // -------------------------------------------------------------------------

    public function getOrCreateConfiguration(): PayrollConfiguration
    {
        $organization = $this->tenantContext->get();

        return PayrollConfiguration::query()->firstOrCreate(
            ['organization_id' => $organization->id],
            [
                'payroll_frequency' => config('hrms.payroll.default_frequency', 'monthly'),
                'currency' => config('hrms.payroll.default_currency', 'INR'),
                'working_days_per_month' => config('hrms.payroll.default_working_days_per_month'),
                'week_off_days' => config('hrms.weekend_days', ['saturday', 'sunday']),
                'overtime_handling' => config('hrms.payroll.default_overtime_handling', 'pay'),
                'rounding_policy' => config('hrms.payroll.default_rounding_policy', 'nearest'),
            ],
        );
    }

    public function updateConfiguration(array $data, User $actor): PayrollConfiguration
    {
        return DB::transaction(function () use ($data, $actor): PayrollConfiguration {
            $configuration = $this->getOrCreateConfiguration();
            $before = $configuration->only([
                'payroll_frequency', 'currency', 'working_days_per_month',
                'week_off_days', 'overtime_handling', 'rounding_policy',
            ]);

            $configuration->update($data);

            $this->auditLogger->log($configuration, 'payroll_configuration_updated', [
                'before' => $before,
                'after' => $configuration->only(array_keys($before)),
            ], $actor);

            return $configuration->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Payroll Calculation Contracts (read-only inputs — no calculation)
    // -------------------------------------------------------------------------

    public function getActiveSalaryAssignment(Employee $employee, CarbonInterface $asOf): ?EmployeeSalaryAssignment
    {
        $day = $asOf->toDateString();

        return EmployeeSalaryAssignment::query()
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $day)
            ->where(function ($query) use ($day) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $day);
            })
            ->with(['salaryStructure.structureComponents.salaryComponent'])
            ->orderByDesc('effective_from')
            ->first();
    }

    public function resolveCalculationContext(Employee $employee, PayrollPeriod $period): array
    {
        $start = Carbon::parse($period->start_date)->startOfDay();
        $end = Carbon::parse($period->end_date)->endOfDay();
        $asOf = $end->copy();

        $assignment = $this->getActiveSalaryAssignment($employee, $asOf);
        $configuration = $this->getOrCreateConfiguration();

        $attendanceRecords = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $approvedLeave = $this->leaveService->getApprovedLeaveForDateRange($employee, $start, $end);
        $unpaidLeave = $approvedLeave->filter(fn ($application) => ! ($application->leaveType?->is_paid ?? true));

        $exitProcess = EmployeeExitProcess::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['in_progress', 'pending_approval', 'completed'])
            ->latest('id')
            ->first();

        return [
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'status' => $employee->status,
                'joining_date' => $employee->joining_date?->toDateString(),
                'exit_date' => $employee->exit_date?->toDateString(),
                'department_id' => $employee->department_id,
                'designation_id' => $employee->designation_id,
                'branch_id' => $employee->branch_id,
            ],
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->toDateString(),
                'status' => $period->status,
            ],
            'configuration' => [
                'payroll_frequency' => $configuration->payroll_frequency,
                'currency' => $configuration->currency,
                'working_days_per_month' => $configuration->working_days_per_month,
                'week_off_days' => $configuration->week_off_days,
                'overtime_handling' => $configuration->overtime_handling,
                'rounding_policy' => $configuration->rounding_policy,
            ],
            'salary_assignment' => $assignment ? [
                'id' => $assignment->id,
                'salary_structure_id' => $assignment->salary_structure_id,
                'effective_from' => $assignment->effective_from->toDateString(),
                'effective_until' => $assignment->effective_until?->toDateString(),
                'annual_ctc' => $assignment->annual_ctc,
                'structure' => $assignment->salaryStructure?->only(['id', 'name', 'effective_date', 'is_active']),
                'components' => $assignment->salaryStructure?->structureComponents->map(fn (SalaryStructureComponent $row) => [
                    'salary_component_id' => $row->salary_component_id,
                    'code' => $row->salaryComponent?->code,
                    'component_type' => $row->salaryComponent?->component_type,
                    'calculation_type' => $row->calculation_type,
                    'amount' => $row->amount,
                    'percentage' => $row->percentage,
                    'based_on_component_id' => $row->based_on_component_id,
                    'formula' => $row->formula,
                ])->all() ?? [],
            ] : null,
            'attendance' => [
                'working_days' => $attendanceRecords->whereIn('status', ['present', 'late', 'half_day'])->count(),
                'overtime_minutes' => (int) $attendanceRecords->sum('overtime_minutes'),
                'summary' => $attendanceRecords->countBy('status')->all(),
                'record_count' => $attendanceRecords->count(),
            ],
            'leave' => [
                'approved_count' => $approvedLeave->count(),
                'approved_days' => (float) $approvedLeave->sum('days'),
                'unpaid_count' => $unpaidLeave->count(),
                'unpaid_days' => (float) $unpaidLeave->sum('days'),
            ],
            'exit' => [
                'exit_date' => $employee->exit_date?->toDateString(),
                'exit_process_status' => $exitProcess?->status,
                'assets_returned' => $exitProcess?->checklist_assets_returned,
            ],
            'calculation' => null,
            'calculation_status' => 'deferred',
            'note' => 'Payroll calculation is not implemented in Phase 10.3.1.',
        ];
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /** @param array<int, array<string, mixed>> $components */
    protected function syncStructureComponents(SalaryStructure $structure, array $components): void
    {
        foreach ($components as $index => $row) {
            $component = SalaryComponent::query()->findOrFail($row['salary_component_id']);

            if ($component->organization_id !== $structure->organization_id) {
                throw ValidationException::withMessages([
                    "components.{$index}.salary_component_id" => 'Component must belong to the same organization.',
                ]);
            }

            $calculationType = $row['calculation_type'] ?? 'fixed';

            SalaryStructureComponent::query()->create([
                'organization_id' => $structure->organization_id,
                'salary_structure_id' => $structure->id,
                'salary_component_id' => $component->id,
                'calculation_type' => $calculationType,
                'amount' => $calculationType === 'fixed' ? ($row['amount'] ?? null) : null,
                'percentage' => $calculationType === 'percentage' ? ($row['percentage'] ?? null) : null,
                'based_on_component_id' => $row['based_on_component_id'] ?? null,
                'formula' => $calculationType === 'formula' ? ($row['formula'] ?? null) : null,
                'sort_order' => $row['sort_order'] ?? $index,
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    protected function normalizeComponentFlags(array $data): array
    {
        foreach (['is_taxable', 'is_recurring', 'formula_supported', 'is_active'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $data[$flag] = (bool) $data[$flag];
            }
        }

        return $data;
    }
}
