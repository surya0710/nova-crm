<?php

namespace App\Services\Hrms;

use App\Events\PayrollEmployeeCalculated;
use App\Events\PayrollRunCompleted;
use App\Events\PayrollRunStarted;
use App\Events\PayrollValidationFailed;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRecovery;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use App\Models\PayrollValidationError;
use App\Models\SalaryAdvance;
use App\Models\SalaryAdvanceRecovery;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    public const ENGINE_VERSION = '10.3.7';

    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected PayrollService $payrollService,
        protected StatutoryComplianceService $statutoryComplianceService,
        protected PayrollAdjustmentService $adjustmentService,
        protected NotificationService $notificationService,
    ) {}

    public function createRun(PayrollPeriod $period, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($period, $actor): PayrollRun {
            $this->assertPeriodCalculable($period);

            $run = PayrollRun::query()->create([
                'organization_id' => $period->organization_id,
                'payroll_period_id' => $period->id,
                'status' => 'draft',
                'triggered_by' => $actor->id,
                'engine_version' => self::ENGINE_VERSION,
            ]);

            $this->auditLogger->log($run, 'payroll_run_created', [
                'payroll_period_id' => $period->id,
                'status' => $run->status,
            ], $actor);

            return $run->load('period');
        });
    }

    public function calculateRun(PayrollRun $run, User $actor): PayrollRun
    {
        return $this->executeRun($run, $actor, recalculating: false);
    }

    public function recalculateRun(PayrollRun $run, User $actor): PayrollRun
    {
        if (! $run->canRecalculate()) {
            throw ValidationException::withMessages([
                'run' => 'Only draft or running payroll runs can be recalculated.',
            ]);
        }

        return $this->executeRun($run, $actor, recalculating: true);
    }

    /**
     * Preview uses the same engine as production but does not persist results.
     *
     * @return array{employee: array<string, mixed>, calculation: array<string, mixed>|null, validation_errors: list<array<string, mixed>>}
     */
    public function previewEmployee(Employee $employee, PayrollPeriod $period): array
    {
        $errors = $this->validateEmployeeForPeriod($employee, $period);

        if ($errors !== []) {
            return [
                'employee' => [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->full_name,
                ],
                'calculation' => null,
                'validation_errors' => $errors,
            ];
        }

        $calculation = $this->calculateEmployeePayroll($employee, $period);
        unset($calculation['_compliance_errors'], $calculation['_statutory_meta']);

        return [
            'employee' => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->full_name,
            ],
            'calculation' => $calculation,
            'validation_errors' => [],
        ];
    }

    /**
     * @return array{period_id: int, employees: list<array<string, mixed>>, success_count: int, error_count: int}
     */
    public function previewPeriod(PayrollPeriod $period): array
    {
        $this->assertPeriodCalculable($period);
        $this->payrollService->getOrCreateConfiguration();

        $employees = $this->eligibleEmployees($period);
        $rows = [];
        $success = 0;
        $errors = 0;

        foreach ($employees as $employee) {
            $preview = $this->previewEmployee($employee, $period);
            $rows[] = $preview;
            if ($preview['validation_errors'] === []) {
                $success++;
            } else {
                $errors++;
            }
        }

        return [
            'period_id' => $period->id,
            'employees' => $rows,
            'success_count' => $success,
            'error_count' => $errors,
        ];
    }

    /**
     * @return list<array{code: string, message: string, context?: array<string, mixed>}>
     */
    public function validateEmployeeForPeriod(Employee $employee, PayrollPeriod $period, ?PayrollRun $run = null): array
    {
        $errors = [];
        $eligibleStatuses = config('hrms.leave_applicable_employee_statuses', ['active', 'probation', 'notice_period']);

        if (! in_array($employee->status, $eligibleStatuses, true)) {
            $errors[] = [
                'code' => 'employee_inactive',
                'message' => 'Employee is not in an active payroll-eligible status.',
                'context' => ['status' => $employee->status],
            ];
        }

        if ($period->isLocked()) {
            $errors[] = [
                'code' => 'period_locked',
                'message' => 'Payroll period is locked or processed and cannot be calculated.',
                'context' => ['status' => $period->status],
            ];
        }

        $configuration = $this->payrollService->getOrCreateConfiguration();
        if (! $configuration->exists) {
            $errors[] = [
                'code' => 'configuration_missing',
                'message' => 'Payroll configuration is required before calculation.',
            ];
        }

        $asOf = Carbon::parse($period->end_date)->endOfDay();
        $assignment = $this->payrollService->getActiveSalaryAssignment($employee, $asOf);
        if (! $assignment) {
            $errors[] = [
                'code' => 'salary_assignment_missing',
                'message' => 'No active salary assignment exists for this employee in the payroll period.',
            ];
        }

        if ($employee->joining_date && $employee->joining_date->gt($period->end_date)) {
            $errors[] = [
                'code' => 'joined_after_period',
                'message' => 'Employee joined after the payroll period ended.',
                'context' => ['joining_date' => $employee->joining_date->toDateString()],
            ];
        }

        if ($employee->exit_date && $employee->exit_date->lt($period->start_date)) {
            $errors[] = [
                'code' => 'exited_before_period',
                'message' => 'Employee exited before the payroll period started.',
                'context' => ['exit_date' => $employee->exit_date->toDateString()],
            ];
        }

        if ($run) {
            $duplicate = PayrollResult::query()
                ->where('payroll_run_id', $run->id)
                ->where('employee_id', $employee->id)
                ->exists();

            if ($duplicate && $run->isImmutable()) {
                $errors[] = [
                    'code' => 'duplicate_result',
                    'message' => 'A payroll result already exists for this employee in a completed run.',
                ];
            }
        }

        return $errors;
    }

    /**
     * Core deterministic calculation. Same path for preview and persisted runs.
     *
     * @return array<string, mixed>
     */
    public function calculateEmployeePayroll(Employee $employee, PayrollPeriod $period): array
    {
        $context = $this->payrollService->resolveCalculationContext($employee, $period);
        $configuration = $context['configuration'];
        $assignment = $context['salary_assignment'];
        $components = $assignment['components'] ?? [];

        $periodWorkingDays = (float) ($configuration['working_days_per_month']
            ?? config('hrms.payroll.default_working_days_per_month', 26));
        if ($periodWorkingDays <= 0) {
            $periodWorkingDays = 26.0;
        }

        $attendanceWorkingDays = (float) ($context['attendance']['working_days'] ?? 0);
        $unpaidLeaveDays = (float) ($context['leave']['unpaid_days'] ?? 0);
        $overtimeMinutes = (int) ($context['attendance']['overtime_minutes'] ?? 0);
        $salaryMode = $configuration['salary_mode']
            ?? config('hrms.payroll.default_salary_mode', 'calendar');

        [$payableDays, $prorationBasis] = $this->resolvePayableDays(
            $salaryMode,
            $periodWorkingDays,
            $attendanceWorkingDays,
            $unpaidLeaveDays,
        );
        $proration = $prorationBasis > 0 ? min(1, $payableDays / $prorationBasis) : 0;

        $statutoryCodes = array_map('strtoupper', config('hrms.payroll.statutory_component_codes', [
            'PF', 'ESI', 'PT', 'IT', 'TDS',
        ]));

        $fixedAmounts = [];
        $earningsLines = [];
        $deductionLines = [];

        // Pass 1: fixed components
        foreach ($components as $row) {
            $code = strtoupper((string) ($row['code'] ?? ''));
            $type = $row['component_type'] ?? 'earning';
            $calcType = $row['calculation_type'] ?? 'fixed';
            $isRecurring = true;

            if ($calcType === 'formula') {
                $line = $this->componentLine($row, 0.0, 'skipped_formula');
                if ($type === 'deduction') {
                    $deductionLines[] = $line;
                } else {
                    $earningsLines[] = $line;
                }

                continue;
            }

            if ($type === 'deduction' && in_array($code, $statutoryCodes, true)) {
                $deductionLines[] = $this->componentLine($row, 0.0, 'skipped_statutory');

                continue;
            }

            if ($calcType !== 'fixed') {
                continue;
            }

            $amount = (float) ($row['amount'] ?? 0);
            $componentMeta = $this->componentRecurringFlag($row['salary_component_id'] ?? null);
            $isRecurring = $componentMeta;
            $applied = $isRecurring ? $amount * $proration : $amount;
            $applied = $this->roundAmount($applied, $configuration['rounding_policy'] ?? 'nearest');
            $fixedAmounts[(int) $row['salary_component_id']] = $applied;

            $line = $this->componentLine($row, $applied, 'calculated');
            if ($type === 'deduction') {
                $deductionLines[] = $line;
            } else {
                $earningsLines[] = $line;
            }
        }

        // Pass 2: percentage components (based on fixed or previously calculated)
        foreach ($components as $row) {
            if (($row['calculation_type'] ?? '') !== 'percentage') {
                continue;
            }

            $code = strtoupper((string) ($row['code'] ?? ''));
            $type = $row['component_type'] ?? 'earning';

            if ($type === 'deduction' && in_array($code, $statutoryCodes, true)) {
                continue;
            }

            $baseId = $row['based_on_component_id'] ?? null;
            $base = $baseId && isset($fixedAmounts[(int) $baseId])
                ? $fixedAmounts[(int) $baseId]
                : array_sum($fixedAmounts);

            $percentage = (float) ($row['percentage'] ?? 0);
            $applied = $this->roundAmount($base * ($percentage / 100), $configuration['rounding_policy'] ?? 'nearest');
            $fixedAmounts[(int) $row['salary_component_id']] = $applied;

            $line = $this->componentLine($row, $applied, 'calculated', [
                'base_amount' => $base,
                'percentage' => $percentage,
            ]);

            if ($type === 'deduction') {
                $deductionLines[] = $line;
            } else {
                $earningsLines[] = $line;
            }
        }

        $totalEarnings = $this->roundAmount(
            collect($earningsLines)->sum('amount'),
            $configuration['rounding_policy'] ?? 'nearest'
        );

        $overtimeAmount = 0.0;
        if (($configuration['overtime_handling'] ?? 'pay') === 'pay' && $overtimeMinutes > 0 && $payableDays > 0) {
            $hourlyRate = ($totalEarnings / $payableDays) / 8;
            $overtimeAmount = $this->roundAmount(
                $hourlyRate * ($overtimeMinutes / 60),
                $configuration['rounding_policy'] ?? 'nearest'
            );
            $earningsLines[] = [
                'salary_component_id' => null,
                'code' => 'OT',
                'name' => 'Overtime',
                'component_type' => 'earning',
                'calculation_type' => 'overtime',
                'amount' => $overtimeAmount,
                'status' => 'calculated',
                'meta' => ['overtime_minutes' => $overtimeMinutes],
            ];
            $totalEarnings = $this->roundAmount(
                $totalEarnings + $overtimeAmount,
                $configuration['rounding_policy'] ?? 'nearest'
            );
        }

        $rounding = $configuration['rounding_policy'] ?? 'nearest';

        // E3 — approved payroll adjustments
        $pendingAdjustments = $this->adjustmentService->approvedForEmployee($employee, $period);
        $adjustmentMeta = [];
        foreach ($pendingAdjustments as $adjustment) {
            $amount = $this->roundAmount((float) $adjustment->amount, $rounding);
            $line = [
                'salary_component_id' => null,
                'code' => strtoupper($adjustment->adjustment_type),
                'name' => $adjustment->title,
                'component_type' => $adjustment->isEarning() ? 'earning' : 'deduction',
                'calculation_type' => 'adjustment',
                'amount' => $amount,
                'status' => 'calculated',
                'meta' => [
                    'payroll_adjustment_id' => $adjustment->id,
                    'adjustment_number' => $adjustment->adjustment_number,
                    'adjustment_type' => $adjustment->adjustment_type,
                ],
            ];
            if ($adjustment->isEarning()) {
                $earningsLines[] = $line;
                $totalEarnings = $this->roundAmount($totalEarnings + $amount, $rounding);
            } else {
                $deductionLines[] = $line;
            }
            $adjustmentMeta[] = [
                'id' => $adjustment->id,
                'type' => $adjustment->adjustment_type,
                'direction' => $adjustment->direction,
                'amount' => $amount,
            ];
        }

        // E1 — loan / advance recoveries (computed into net; applied on persist)
        $recoveries = $this->pendingRecoveriesForEmployee($employee);
        $recoveryTotal = 0.0;
        foreach ($recoveries['loans'] as $loanRow) {
            $amount = $this->roundAmount((float) $loanRow['amount'], $rounding);
            $deductionLines[] = [
                'salary_component_id' => null,
                'code' => 'LOAN',
                'name' => 'Loan Recovery',
                'component_type' => 'deduction',
                'calculation_type' => 'recovery',
                'amount' => $amount,
                'status' => 'calculated',
                'meta' => [
                    'employee_loan_id' => $loanRow['employee_loan_id'],
                    'loan_number' => $loanRow['loan_number'],
                ],
            ];
            $recoveryTotal += $amount;
        }
        foreach ($recoveries['advances'] as $advanceRow) {
            $amount = $this->roundAmount((float) $advanceRow['amount'], $rounding);
            $deductionLines[] = [
                'salary_component_id' => null,
                'code' => 'ADVANCE',
                'name' => 'Advance Recovery',
                'component_type' => 'deduction',
                'calculation_type' => 'recovery',
                'amount' => $amount,
                'status' => 'calculated',
                'meta' => [
                    'salary_advance_id' => $advanceRow['salary_advance_id'],
                    'advance_number' => $advanceRow['advance_number'],
                ],
            ];
            $recoveryTotal += $amount;
        }

        $totalDeductions = $this->roundAmount(
            collect($deductionLines)
                ->filter(fn (array $line) => ($line['component_type'] ?? '') !== 'employer_contribution')
                ->sum('amount'),
            $rounding
        );
        $gross = $totalEarnings;
        $net = $this->roundAmount($gross - $totalDeductions, $rounding);

        $snapshot = [
            'engine_version' => self::ENGINE_VERSION,
            'employee' => $context['employee'],
            'period' => $context['period'],
            'configuration' => $configuration,
            'salary_assignment' => [
                'id' => $assignment['id'] ?? null,
                'salary_structure_id' => $assignment['salary_structure_id'] ?? null,
                'structure' => $assignment['structure'] ?? null,
                'annual_ctc' => $assignment['annual_ctc'] ?? null,
                'effective_from' => $assignment['effective_from'] ?? null,
                'effective_until' => $assignment['effective_until'] ?? null,
            ],
            'attendance' => $context['attendance'],
            'leave' => $context['leave'],
            'exit' => $context['exit'],
            'proration' => [
                'salary_mode' => $salaryMode,
                'period_working_days' => $periodWorkingDays,
                'attendance_working_days' => $attendanceWorkingDays,
                'unpaid_leave_days' => $unpaidLeaveDays,
                'payable_days' => $payableDays,
                'proration_basis' => $prorationBasis,
                'factor' => $proration,
            ],
            'adjustments' => $adjustmentMeta,
            'recoveries' => [
                'loans' => $recoveries['loans'],
                'advances' => $recoveries['advances'],
                'total' => $this->roundAmount($recoveryTotal, $rounding),
            ],
            'earnings' => $earningsLines,
            'deductions' => $deductionLines,
            'totals' => [
                'gross_salary' => $gross,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $net,
                'overtime_minutes' => $overtimeMinutes,
                'overtime_amount' => $overtimeAmount,
                'recovery_total' => $this->roundAmount($recoveryTotal, $rounding),
            ],
        ];

        $hash = $this->hashSnapshot($snapshot);

        $baseCalculation = [
            'gross_salary' => $gross,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_salary' => $net,
            'working_days' => $periodWorkingDays,
            'payable_days' => $payableDays,
            'overtime_minutes' => $overtimeMinutes,
            'overtime_amount' => $overtimeAmount,
            'snapshot' => $snapshot,
            'calculation_hash' => $hash,
            'version' => 1,
        ];

        $statutory = $this->statutoryComplianceService->applyToPayrollCalculation(
            $employee,
            $period,
            $baseCalculation,
            $configuration['rounding_policy'] ?? 'nearest',
        );

        $calculation = $statutory['calculation'];
        $calculation['_statutory_meta'] = $statutory['statutory_meta'];
        $calculation['_compliance_errors'] = $statutory['compliance_errors'];

        return $calculation;
    }

    protected function executeRun(PayrollRun $run, User $actor, bool $recalculating): PayrollRun
    {
        return DB::transaction(function () use ($run, $actor, $recalculating): PayrollRun {
            $run->loadMissing('period');
            $period = $run->period;
            $this->assertPeriodCalculable($period);

            if ($run->isImmutable()) {
                throw ValidationException::withMessages([
                    'run' => 'Completed payroll runs cannot be calculated again.',
                ]);
            }

            if ($recalculating) {
                $this->releaseRecoveriesForRun($run, $actor);
                $this->adjustmentService->releaseAppliedForRun($run, $actor);
                $run->results()->delete();
                $run->validationErrors()->delete();
                $this->auditLogger->log($run, 'payroll_recalculated', [
                    'payroll_period_id' => $period->id,
                ], $actor);
            }

            $run->update([
                'status' => 'running',
                'started_at' => $run->started_at ?? now(),
                'completed_at' => null,
                'triggered_by' => $actor->id,
                'engine_version' => self::ENGINE_VERSION,
            ]);

            $this->auditLogger->log($run, 'payroll_calculation_started', [
                'payroll_period_id' => $period->id,
                'recalculating' => $recalculating,
            ], $actor);

            event(PayrollRunStarted::forModel($run, [
                'actor_id' => $actor->id,
                'payroll_period_id' => $period->id,
            ]));

            $employees = $this->eligibleEmployees($period);
            $success = 0;
            $errorCount = 0;

            foreach ($employees as $employee) {
                $validationErrors = $this->validateEmployeeForPeriod($employee, $period, $run);

                if ($validationErrors !== []) {
                    $errorCount++;
                    foreach ($validationErrors as $error) {
                        $record = PayrollValidationError::query()->create([
                            'organization_id' => $run->organization_id,
                            'payroll_run_id' => $run->id,
                            'employee_id' => $employee->id,
                            'code' => $error['code'],
                            'message' => $error['message'],
                            'context' => $error['context'] ?? [],
                        ]);

                        event(PayrollValidationFailed::forModel($record, [
                            'actor_id' => $actor->id,
                            'employee_id' => $employee->id,
                            'payroll_run_id' => $run->id,
                            'code' => $error['code'],
                        ]));
                    }

                    continue;
                }

                if (PayrollResult::query()
                    ->where('payroll_run_id', $run->id)
                    ->where('employee_id', $employee->id)
                    ->exists()) {
                    $errorCount++;
                    $record = PayrollValidationError::query()->create([
                        'organization_id' => $run->organization_id,
                        'payroll_run_id' => $run->id,
                        'employee_id' => $employee->id,
                        'code' => 'duplicate_result',
                        'message' => 'A payroll result already exists for this employee in this run.',
                        'context' => [],
                    ]);
                    event(PayrollValidationFailed::forModel($record, [
                        'actor_id' => $actor->id,
                        'employee_id' => $employee->id,
                        'payroll_run_id' => $run->id,
                    ]));

                    continue;
                }

                $calculation = $this->calculateEmployeePayroll($employee, $period);
                $complianceErrors = $calculation['_compliance_errors'] ?? [];
                $statutoryMeta = $calculation['_statutory_meta'] ?? [];
                unset($calculation['_compliance_errors'], $calculation['_statutory_meta']);

                $result = PayrollResult::query()->create([
                    'organization_id' => $run->organization_id,
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'gross_salary' => $calculation['gross_salary'],
                    'total_earnings' => $calculation['total_earnings'],
                    'total_deductions' => $calculation['total_deductions'],
                    'net_salary' => $calculation['net_salary'],
                    'working_days' => $calculation['working_days'],
                    'payable_days' => $calculation['payable_days'],
                    'overtime_minutes' => $calculation['overtime_minutes'],
                    'overtime_amount' => $calculation['overtime_amount'],
                    'snapshot' => $calculation['snapshot'],
                    'calculation_hash' => $calculation['calculation_hash'],
                    'version' => $calculation['version'],
                ]);

                $this->persistRecoveriesFromSnapshot($employee, $period, $run, $calculation['snapshot'] ?? [], $actor);
                $ids = collect($calculation['snapshot']['adjustments'] ?? [])->pluck('id')->filter()->all();
                if ($ids !== []) {
                    $this->adjustmentService->markApplied(
                        PayrollAdjustment::query()->whereIn('id', $ids)->where('status', 'approved')->get(),
                        $run,
                        $actor,
                    );
                }

                $this->statutoryComplianceService->recordPayrollStatutoryOutcome(
                    $employee,
                    $period,
                    $run,
                    $result,
                    $complianceErrors,
                    $statutoryMeta,
                    $actor,
                );

                $success++;
                event(PayrollEmployeeCalculated::forModel($result, [
                    'actor_id' => $actor->id,
                    'employee_id' => $employee->id,
                    'payroll_run_id' => $run->id,
                    'net_salary' => $calculation['net_salary'],
                ]));
            }

            $run->update([
                'status' => 'calculated',
                'completed_at' => now(),
                'employee_count' => $employees->count(),
                'success_count' => $success,
                'error_count' => $errorCount,
            ]);

            $this->auditLogger->log($run, 'payroll_calculation_completed', [
                'employee_count' => $employees->count(),
                'success_count' => $success,
                'error_count' => $errorCount,
            ], $actor);

            event(PayrollRunCompleted::forModel($run, [
                'actor_id' => $actor->id,
                'success_count' => $success,
                'error_count' => $errorCount,
            ]));

            try {
                $this->notificationService->send(
                    $run->organization_id,
                    $actor->id,
                    __('Payroll generated'),
                    __('Payroll calculation completed for :period (:success succeeded, :errors errors).', [
                        'period' => $period->name,
                        'success' => $success,
                        'errors' => $errorCount,
                    ]),
                    '/hrms/payroll/runs/'.$run->id,
                );
            } catch (\Throwable) {
                // best-effort
            }

            return $run->fresh(['period', 'results', 'validationErrors']);
        });
    }

    /** @return Collection<int, Employee> */
    protected function eligibleEmployees(PayrollPeriod $period): Collection
    {
        $statuses = config('hrms.leave_applicable_employee_statuses', ['active', 'probation', 'notice_period']);

        return Employee::query()
            ->whereIn('status', $statuses)
            ->where(function ($query) use ($period) {
                $query->whereNull('joining_date')
                    ->orWhereDate('joining_date', '<=', $period->end_date->toDateString());
            })
            ->where(function ($query) use ($period) {
                $query->whereNull('exit_date')
                    ->orWhereDate('exit_date', '>=', $period->start_date->toDateString());
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{0: float, 1: float} [payableDays, prorationBasis]
     */
    protected function resolvePayableDays(
        string $salaryMode,
        float $periodWorkingDays,
        float $attendanceWorkingDays,
        float $unpaidLeaveDays,
    ): array {
        return match ($salaryMode) {
            'attendance' => [
                max(0, round($attendanceWorkingDays, 2)),
                max($periodWorkingDays, $attendanceWorkingDays, 1),
            ],
            'leave' => [
                max(0, round($periodWorkingDays - $unpaidLeaveDays, 2)),
                $periodWorkingDays,
            ],
            default => [ // calendar
                max(0, round($periodWorkingDays - $unpaidLeaveDays, 2)),
                $periodWorkingDays,
            ],
        };
    }

    /**
     * Preview-safe recovery amounts (does not mutate balances).
     *
     * @return array{loans: list<array<string, mixed>>, advances: list<array<string, mixed>>}
     */
    public function pendingRecoveriesForEmployee(Employee $employee): array
    {
        $loans = [];
        foreach (EmployeeLoan::query()->where('employee_id', $employee->id)->where('status', 'active')->get() as $loan) {
            $amount = min((float) $loan->monthly_recovery, (float) $loan->outstanding_balance);
            if ($amount <= 0) {
                continue;
            }
            $loans[] = [
                'employee_loan_id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'amount' => round($amount, 2),
            ];
        }

        $advances = [];
        foreach (SalaryAdvance::query()->where('employee_id', $employee->id)->where('status', 'active')->get() as $advance) {
            $amount = min((float) $advance->monthly_recovery, (float) $advance->outstanding_balance);
            if ($amount <= 0) {
                continue;
            }
            $advances[] = [
                'salary_advance_id' => $advance->id,
                'advance_number' => $advance->advance_number,
                'amount' => round($amount, 2),
            ];
        }

        return ['loans' => $loans, 'advances' => $advances];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    protected function persistRecoveriesFromSnapshot(
        Employee $employee,
        PayrollPeriod $period,
        PayrollRun $run,
        array $snapshot,
        User $actor,
    ): void {
        foreach ($snapshot['recoveries']['loans'] ?? [] as $row) {
            $loanId = (int) ($row['employee_loan_id'] ?? 0);
            $amount = round((float) ($row['amount'] ?? 0), 2);
            if ($loanId <= 0 || $amount <= 0) {
                continue;
            }

            $already = EmployeeLoanRecovery::query()
                ->where('employee_loan_id', $loanId)
                ->where('payroll_run_id', $run->id)
                ->exists();
            if ($already) {
                continue;
            }

            $loan = EmployeeLoan::query()->whereKey($loanId)->where('employee_id', $employee->id)->first();
            if (! $loan) {
                continue;
            }

            EmployeeLoanRecovery::query()->create([
                'organization_id' => $run->organization_id,
                'employee_loan_id' => $loan->id,
                'payroll_run_id' => $run->id,
                'payroll_period_id' => $period->id,
                'amount' => $amount,
                'recovery_type' => 'payroll',
                'recovered_at' => now(),
                'notes' => 'Payroll calculation recovery',
                'recovered_by' => $actor->id,
            ]);

            $newBalance = round((float) $loan->outstanding_balance - $amount, 2);
            $loan->update([
                'outstanding_balance' => max(0, $newBalance),
                'status' => $newBalance <= 0 ? 'closed' : 'active',
                'closed_at' => $newBalance <= 0 ? now() : null,
                'closed_by' => $newBalance <= 0 ? $actor->id : null,
                'closure_reason' => $newBalance <= 0 ? 'Fully recovered via payroll' : null,
            ]);

            $this->auditLogger->log($loan, 'payroll_loan_recovery_applied', [
                'payroll_run_id' => $run->id,
                'amount' => $amount,
                'outstanding_balance' => max(0, $newBalance),
            ], $actor);
        }

        foreach ($snapshot['recoveries']['advances'] ?? [] as $row) {
            $advanceId = (int) ($row['salary_advance_id'] ?? 0);
            $amount = round((float) ($row['amount'] ?? 0), 2);
            if ($advanceId <= 0 || $amount <= 0) {
                continue;
            }

            $already = SalaryAdvanceRecovery::query()
                ->where('salary_advance_id', $advanceId)
                ->where('payroll_run_id', $run->id)
                ->exists();
            if ($already) {
                continue;
            }

            $advance = SalaryAdvance::query()->whereKey($advanceId)->where('employee_id', $employee->id)->first();
            if (! $advance) {
                continue;
            }

            SalaryAdvanceRecovery::query()->create([
                'organization_id' => $run->organization_id,
                'salary_advance_id' => $advance->id,
                'payroll_run_id' => $run->id,
                'payroll_period_id' => $period->id,
                'amount' => $amount,
                'recovery_type' => 'payroll',
                'recovered_at' => now(),
                'notes' => 'Payroll calculation recovery',
                'recovered_by' => $actor->id,
            ]);

            $newBalance = round((float) $advance->outstanding_balance - $amount, 2);
            $advance->update([
                'outstanding_balance' => max(0, $newBalance),
                'status' => $newBalance <= 0 ? 'closed' : 'active',
            ]);

            $this->auditLogger->log($advance, 'payroll_advance_recovery_applied', [
                'payroll_run_id' => $run->id,
                'amount' => $amount,
                'outstanding_balance' => max(0, $newBalance),
            ], $actor);
        }
    }

    protected function releaseRecoveriesForRun(PayrollRun $run, User $actor): void
    {
        $loanRecoveries = EmployeeLoanRecovery::query()->where('payroll_run_id', $run->id)->get();
        foreach ($loanRecoveries as $recovery) {
            $loan = EmployeeLoan::query()->find($recovery->employee_loan_id);
            if ($loan) {
                $restored = round((float) $loan->outstanding_balance + (float) $recovery->amount, 2);
                $loan->update([
                    'outstanding_balance' => $restored,
                    'status' => 'active',
                    'closed_at' => null,
                    'closed_by' => null,
                    'closure_reason' => null,
                ]);
            }
            $this->auditLogger->log($recovery, 'payroll_loan_recovery_released', [
                'payroll_run_id' => $run->id,
                'amount' => $recovery->amount,
            ], $actor);
            $recovery->delete();
        }

        $advanceRecoveries = SalaryAdvanceRecovery::query()->where('payroll_run_id', $run->id)->get();
        foreach ($advanceRecoveries as $recovery) {
            $advance = SalaryAdvance::query()->find($recovery->salary_advance_id);
            if ($advance) {
                $restored = round((float) $advance->outstanding_balance + (float) $recovery->amount, 2);
                $advance->update([
                    'outstanding_balance' => $restored,
                    'status' => 'active',
                ]);
            }
            $this->auditLogger->log($recovery, 'payroll_advance_recovery_released', [
                'payroll_run_id' => $run->id,
                'amount' => $recovery->amount,
            ], $actor);
            $recovery->delete();
        }
    }

    protected function assertPeriodCalculable(PayrollPeriod $period): void
    {
        if ($period->isLocked()) {
            throw ValidationException::withMessages([
                'period' => 'Locked or processed payroll periods cannot be used for calculation.',
            ]);
        }
    }

    /** @param array<string, mixed> $row */
    protected function componentLine(array $row, float $amount, string $status, array $meta = []): array
    {
        return [
            'salary_component_id' => $row['salary_component_id'] ?? null,
            'code' => $row['code'] ?? null,
            'name' => $row['code'] ?? null,
            'component_type' => $row['component_type'] ?? null,
            'calculation_type' => $row['calculation_type'] ?? null,
            'amount' => $amount,
            'status' => $status,
            'meta' => $meta,
        ];
    }

    protected function componentRecurringFlag(?int $componentId): bool
    {
        if (! $componentId) {
            return true;
        }

        $component = SalaryComponent::query()->find($componentId);

        return $component?->is_recurring ?? true;
    }

    protected function roundAmount(float $amount, string $policy): float
    {
        return match ($policy) {
            'up' => ceil($amount * 100) / 100,
            'down' => floor($amount * 100) / 100,
            'none' => round($amount, 4),
            default => round($amount, 2),
        };
    }

    /** @param array<string, mixed> $snapshot */
    protected function hashSnapshot(array $snapshot): string
    {
        $canonical = $snapshot;
        unset($canonical['engine_version']);

        return hash('sha256', json_encode($this->canonicalize($canonical)));
    }

    protected function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
