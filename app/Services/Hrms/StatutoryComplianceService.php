<?php

namespace App\Services\Hrms;

use App\Events\PayrollComplianceFailed;
use App\Events\PayrollStatutoryCalculated;
use App\Events\StatutoryProfileUpdated;
use App\Events\StatutoryRuleChanged;
use App\Models\Employee;
use App\Models\EmployeeStatutoryProfile;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\PayrollRun;
use App\Models\StatutoryComplianceError;
use App\Models\StatutoryRuleSet;
use App\Models\StatutoryRuleVersion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatutoryComplianceService
{
    public const ENGINE_VERSION = '10.3.7';

    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected TdsCalculationService $tdsCalculationService,
    ) {}

    /**
     * Apply statutory calculations to a base payroll calculation payload.
     * Called by PayrollCalculationService — owns all statutory math.
     *
     * @param  array<string, mixed>  $baseCalculation
     * @return array{calculation: array<string, mixed>, compliance_errors: list<array<string, mixed>>, statutory_meta: array<string, mixed>}
     */
    public function applyToPayrollCalculation(
        Employee $employee,
        PayrollPeriod $period,
        array $baseCalculation,
        string $roundingPolicy = 'nearest',
    ): array {
        $asOf = Carbon::parse($period->end_date)->endOfDay();
        $ruleSet = $this->resolveActiveRuleSet();
        $version = $ruleSet ? $this->resolveRuleVersion($ruleSet, $asOf) : null;
        $profile = $this->getProfileForEmployee($employee);

        $complianceErrors = $this->validateCompliance($employee, $period, $profile, $ruleSet, $version);

        if (! $ruleSet || ! $version) {
            return [
                'calculation' => $baseCalculation,
                'compliance_errors' => $complianceErrors,
                'statutory_meta' => [
                    'engine_version' => self::ENGINE_VERSION,
                    'applied' => false,
                    'reason' => $ruleSet ? 'rule_version_missing' : 'rule_set_missing',
                ],
            ];
        }

        if (! $profile) {
            return [
                'calculation' => $baseCalculation,
                'compliance_errors' => $complianceErrors,
                'statutory_meta' => [
                    'engine_version' => self::ENGINE_VERSION,
                    'applied' => false,
                    'reason' => 'profile_missing',
                    'rule_set_id' => $ruleSet->id,
                    'rule_version_id' => $version->id,
                ],
            ];
        }

        $config = $version->configuration ?? [];
        $gross = (float) ($baseCalculation['gross_salary'] ?? 0);
        $earnings = $baseCalculation['snapshot']['earnings'] ?? [];
        $month = (int) Carbon::parse($period->start_date)->month;

        $pf = $this->calculatePf($profile, $config, $gross, $earnings, $roundingPolicy);
        $esi = $this->calculateEsi($profile, $config, $gross, $roundingPolicy);
        $pt = $this->calculateProfessionalTax($profile, $config, $gross, $month, $roundingPolicy);
        $tds = $this->tdsCalculationService->calculateForPayroll(
            $employee,
            $period,
            $profile,
            $baseCalculation,
            $config['tds'] ?? [],
            $roundingPolicy,
        );

        $components = $this->buildStatutoryComponents($pf, $esi, $pt, $tds);
        $employeeDeductions = collect($components)
            ->where('affects_net', true)
            ->sum('amount');

        $deductionLines = $baseCalculation['snapshot']['deductions'] ?? [];
        // Drop placeholder structure statutory lines (amount 0 / skipped) — engine owns these.
        $statutoryCodes = array_map('strtoupper', config('hrms.payroll.statutory_component_codes', [
            'PF', 'ESI', 'PT', 'IT', 'TDS',
        ]));
        $engineCodes = array_map('strtoupper', array_column($components, 'code'));
        $deductionLines = array_values(array_filter(
            $deductionLines,
            function (array $line) use ($statutoryCodes, $engineCodes): bool {
                $code = strtoupper((string) ($line['code'] ?? ''));
                if (in_array($code, $engineCodes, true)) {
                    return false;
                }
                if (in_array($code, $statutoryCodes, true) && ($line['status'] ?? '') === 'skipped_statutory') {
                    return false;
                }

                return true;
            }
        ));

        foreach ($components as $component) {
            $deductionLines[] = [
                'salary_component_id' => null,
                'code' => $component['code'],
                'name' => $component['name'],
                'component_type' => $component['component_type'],
                'calculation_type' => 'statutory',
                'amount' => $component['amount'],
                'status' => $component['status'],
                'meta' => $component['meta'],
                'affects_net' => $component['affects_net'],
            ];
        }

        $nonStatutoryDeductions = collect($deductionLines)
            ->filter(fn (array $line) => ($line['affects_net'] ?? (($line['component_type'] ?? '') === 'deduction')))
            ->filter(fn (array $line) => ($line['component_type'] ?? '') !== 'employer_contribution')
            ->sum('amount');

        $totalDeductions = $this->roundAmount((float) $nonStatutoryDeductions, $roundingPolicy);
        $net = $this->roundAmount($gross - $totalDeductions, $roundingPolicy);

        $statutorySnapshot = [
            'engine_version' => self::ENGINE_VERSION,
            'rule_set' => [
                'id' => $ruleSet->id,
                'code' => $ruleSet->code,
                'name' => $ruleSet->name,
                'jurisdiction' => $ruleSet->jurisdiction,
            ],
            'rule_version' => [
                'id' => $version->id,
                'version' => $version->version,
                'effective_from' => $version->effective_from?->toDateString(),
                'effective_until' => $version->effective_until?->toDateString(),
                'configuration' => $config,
            ],
            'profile' => [
                'id' => $profile->id,
                'pf_eligible' => $profile->pf_eligible,
                'esi_eligible' => $profile->esi_eligible,
                'professional_tax_state' => $profile->professional_tax_state,
                'tax_regime' => $profile->tax_regime,
                'has_pan' => filled($profile->pan),
                'has_uan' => filled($profile->pf_uan),
                'has_esi_number' => filled($profile->esi_number),
            ],
            'pf' => $pf,
            'esi' => $esi,
            'professional_tax' => $pt,
            'tds' => $tds,
            'components' => $components,
        ];

        $snapshot = $baseCalculation['snapshot'];
        $snapshot['engine_version'] = self::ENGINE_VERSION;
        $snapshot['deductions'] = $deductionLines;
        $snapshot['statutory'] = $statutorySnapshot;
        $snapshot['totals']['total_deductions'] = $totalDeductions;
        $snapshot['totals']['net_salary'] = $net;
        $snapshot['totals']['statutory_employee_deductions'] = $this->roundAmount((float) $employeeDeductions, $roundingPolicy);
        $snapshot['totals']['employer_contributions'] = $this->roundAmount(
            (float) collect($components)->where('affects_net', false)->sum('amount'),
            $roundingPolicy
        );

        $calculation = $baseCalculation;
        $calculation['total_deductions'] = $totalDeductions;
        $calculation['net_salary'] = $net;
        $calculation['snapshot'] = $snapshot;
        $calculation['calculation_hash'] = $this->hashSnapshot($snapshot);

        return [
            'calculation' => $calculation,
            'compliance_errors' => $complianceErrors,
            'statutory_meta' => [
                'engine_version' => self::ENGINE_VERSION,
                'applied' => true,
                'rule_set_id' => $ruleSet->id,
                'rule_version_id' => $version->id,
                'profile_id' => $profile->id,
                'component_count' => count($components),
            ],
        ];
    }

    /**
     * Persist compliance errors and emit workflow events after a payroll result is saved.
     *
     * @param  list<array{code: string, message: string, context?: array<string, mixed>}>  $errors
     * @param  array<string, mixed>  $statutoryMeta
     */
    public function recordPayrollStatutoryOutcome(
        Employee $employee,
        PayrollPeriod $period,
        ?PayrollRun $run,
        ?PayrollResult $result,
        array $errors,
        array $statutoryMeta,
        ?User $actor = null,
    ): void {
        foreach ($errors as $error) {
            $record = StatutoryComplianceError::query()->create([
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'payroll_run_id' => $run?->id,
                'payroll_result_id' => $result?->id,
                'statutory_rule_set_id' => $statutoryMeta['rule_set_id'] ?? null,
                'statutory_rule_version_id' => $statutoryMeta['rule_version_id'] ?? null,
                'code' => $error['code'],
                'message' => $error['message'],
                'context' => array_merge($error['context'] ?? [], [
                    'payroll_period_id' => $period->id,
                ]),
            ]);

            $this->auditLogger->log($record, 'statutory_compliance_failed', [
                'code' => $error['code'],
                'employee_id' => $employee->id,
            ], $actor);

            event(PayrollComplianceFailed::forModel($record, [
                'actor_id' => $actor?->id,
                'employee_id' => $employee->id,
                'code' => $error['code'],
            ]));
        }

        if (($statutoryMeta['applied'] ?? false) && $result) {
            $this->auditLogger->log($result, 'statutory_calculated', [
                'employee_id' => $employee->id,
                'rule_version_id' => $statutoryMeta['rule_version_id'] ?? null,
                'component_count' => $statutoryMeta['component_count'] ?? 0,
            ], $actor);

            event(PayrollStatutoryCalculated::forModel($result, [
                'actor_id' => $actor?->id,
                'employee_id' => $employee->id,
                'rule_version_id' => $statutoryMeta['rule_version_id'] ?? null,
            ]));
        }
    }

    public function resolveActiveRuleSet(?int $organizationId = null): ?StatutoryRuleSet
    {
        $organizationId ??= $this->tenantContext->id();

        return StatutoryRuleSet::query()
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }

    public function resolveRuleVersion(StatutoryRuleSet $ruleSet, CarbonInterface $asOf): ?StatutoryRuleVersion
    {
        $date = Carbon::parse($asOf)->toDateString();

        return StatutoryRuleVersion::query()
            ->where('statutory_rule_set_id', $ruleSet->id)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public function getProfileForEmployee(Employee $employee): ?EmployeeStatutoryProfile
    {
        return EmployeeStatutoryProfile::query()
            ->where('employee_id', $employee->id)
            ->first();
    }

    /**
     * @return list<array{code: string, message: string, context?: array<string, mixed>}>
     */
    public function validateCompliance(
        Employee $employee,
        PayrollPeriod $period,
        ?EmployeeStatutoryProfile $profile = null,
        ?StatutoryRuleSet $ruleSet = null,
        ?StatutoryRuleVersion $version = null,
    ): array {
        $profile ??= $this->getProfileForEmployee($employee);
        $ruleSet ??= $this->resolveActiveRuleSet($employee->organization_id);
        $asOf = Carbon::parse($period->end_date)->endOfDay();
        $version ??= $ruleSet ? $this->resolveRuleVersion($ruleSet, $asOf) : null;

        $errors = [];

        if (! $ruleSet) {
            $errors[] = [
                'code' => 'missing_rule_set',
                'message' => 'No active statutory rule set is configured for this organization.',
            ];
        } elseif (! $version) {
            $errors[] = [
                'code' => 'missing_rule_version',
                'message' => 'No statutory rule version is effective for the payroll period.',
                'context' => [
                    'rule_set_id' => $ruleSet->id,
                    'as_of' => $asOf->toDateString(),
                ],
            ];
        }

        if (! $profile) {
            $errors[] = [
                'code' => 'missing_statutory_profile',
                'message' => 'Employee does not have a statutory profile.',
                'context' => ['employee_id' => $employee->id],
            ];

            return $errors;
        }

        if ($profile->pf_eligible && blank($profile->pf_uan)) {
            $errors[] = [
                'code' => 'missing_uan',
                'message' => 'PF-eligible employee is missing UAN.',
                'context' => ['employee_id' => $employee->id],
            ];
        }

        if ($profile->esi_eligible && blank($profile->esi_number)) {
            $errors[] = [
                'code' => 'missing_esi_number',
                'message' => 'ESI-eligible employee is missing ESI number.',
                'context' => ['employee_id' => $employee->id],
            ];
        }

        if (blank($profile->pan)) {
            $errors[] = [
                'code' => 'missing_pan',
                'message' => 'Employee statutory profile is missing PAN.',
                'context' => ['employee_id' => $employee->id],
            ];
        }

        return $errors;
    }

    /**
     * Run compliance validation for all eligible employees and persist errors.
     *
     * @return array{validated: int, error_count: int, errors: Collection<int, StatutoryComplianceError>}
     */
    public function runOrganizationValidation(?PayrollPeriod $period = null, ?User $actor = null): array
    {
        $ruleSet = $this->resolveActiveRuleSet();
        $period ??= $this->defaultValidationPeriod();
        $asOf = Carbon::parse($period->end_date)->endOfDay();
        $version = $ruleSet ? $this->resolveRuleVersion($ruleSet, $asOf) : null;

        StatutoryComplianceError::query()
            ->whereNull('payroll_run_id')
            ->delete();

        $statuses = config('hrms.leave_applicable_employee_statuses', ['active', 'probation', 'notice_period']);
        $employees = Employee::query()->whereIn('status', $statuses)->orderBy('id')->get();

        $created = collect();
        foreach ($employees as $employee) {
            $profile = $this->getProfileForEmployee($employee);
            $errors = $this->validateCompliance($employee, $period, $profile, $ruleSet, $version);

            foreach ($errors as $error) {
                $record = StatutoryComplianceError::query()->create([
                    'organization_id' => $employee->organization_id,
                    'employee_id' => $employee->id,
                    'statutory_rule_set_id' => $ruleSet?->id,
                    'statutory_rule_version_id' => $version?->id,
                    'code' => $error['code'],
                    'message' => $error['message'],
                    'context' => $error['context'] ?? [],
                ]);

                $this->auditLogger->log($record, 'statutory_compliance_failed', [
                    'code' => $error['code'],
                    'employee_id' => $employee->id,
                ], $actor);

                event(PayrollComplianceFailed::forModel($record, [
                    'actor_id' => $actor?->id,
                    'employee_id' => $employee->id,
                    'code' => $error['code'],
                ]));

                $created->push($record);
            }
        }

        return [
            'validated' => $employees->count(),
            'error_count' => $created->count(),
            'errors' => $created,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertProfile(Employee $employee, array $data, User $actor): EmployeeStatutoryProfile
    {
        return DB::transaction(function () use ($employee, $data, $actor): EmployeeStatutoryProfile {
            $profile = EmployeeStatutoryProfile::query()->updateOrCreate(
                [
                    'organization_id' => $employee->organization_id,
                    'employee_id' => $employee->id,
                ],
                [
                    'pf_eligible' => (bool) ($data['pf_eligible'] ?? false),
                    'pf_uan' => $data['pf_uan'] ?? null,
                    'esi_eligible' => (bool) ($data['esi_eligible'] ?? false),
                    'esi_number' => $data['esi_number'] ?? null,
                    'professional_tax_state' => $data['professional_tax_state'] ?? null,
                    'tax_regime' => $data['tax_regime'] ?? null,
                    'pan' => $data['pan'] ?? null,
                    'aadhaar' => $data['aadhaar'] ?? null,
                    'tan_reference' => $data['tan_reference'] ?? null,
                ]
            );

            $this->auditLogger->log($profile, 'statutory_profile_updated', [
                'employee_id' => $employee->id,
                'pf_eligible' => $profile->pf_eligible,
                'esi_eligible' => $profile->esi_eligible,
            ], $actor);

            event(StatutoryProfileUpdated::forModel($profile, [
                'actor_id' => $actor->id,
                'employee_id' => $employee->id,
            ]));

            return $profile->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRuleSet(array $data, User $actor): StatutoryRuleSet
    {
        return DB::transaction(function () use ($data, $actor): StatutoryRuleSet {
            $activate = (bool) ($data['is_active'] ?? false);

            $ruleSet = StatutoryRuleSet::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'jurisdiction' => $data['jurisdiction'] ?? 'IN',
                'description' => $data['description'] ?? null,
                'is_active' => false,
            ]);

            $configuration = $data['configuration']
                ?? config('hrms.statutory.default_india_configuration', []);

            $version = StatutoryRuleVersion::query()->create([
                'organization_id' => $ruleSet->organization_id,
                'statutory_rule_set_id' => $ruleSet->id,
                'version' => $data['version'] ?? '1.0',
                'effective_from' => $data['effective_from'] ?? now()->toDateString(),
                'effective_until' => $data['effective_until'] ?? null,
                'jurisdiction' => $ruleSet->jurisdiction,
                'configuration' => $configuration,
                'is_active' => true,
            ]);

            if ($activate) {
                $this->activateRuleSet($ruleSet, $actor);
            }

            $this->auditLogger->log($ruleSet, 'statutory_rule_set_created', [
                'code' => $ruleSet->code,
                'version_id' => $version->id,
            ], $actor);

            event(StatutoryRuleChanged::forModel($ruleSet, [
                'actor_id' => $actor->id,
                'action' => 'created',
                'version_id' => $version->id,
            ]));

            return $ruleSet->fresh('versions');
        });
    }

    public function activateRuleSet(StatutoryRuleSet $ruleSet, User $actor): StatutoryRuleSet
    {
        return DB::transaction(function () use ($ruleSet, $actor): StatutoryRuleSet {
            StatutoryRuleSet::query()
                ->where('organization_id', $ruleSet->organization_id)
                ->where('id', '!=', $ruleSet->id)
                ->update(['is_active' => false]);

            $ruleSet->update(['is_active' => true]);

            $this->auditLogger->log($ruleSet, 'statutory_rule_activated', [
                'code' => $ruleSet->code,
            ], $actor);

            event(StatutoryRuleChanged::forModel($ruleSet->fresh(), [
                'actor_id' => $actor->id,
                'action' => 'activated',
            ]));

            return $ruleSet->fresh('versions');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRuleVersion(StatutoryRuleSet $ruleSet, array $data, User $actor): StatutoryRuleVersion
    {
        return DB::transaction(function () use ($ruleSet, $data, $actor): StatutoryRuleVersion {
            $version = StatutoryRuleVersion::query()->create([
                'organization_id' => $ruleSet->organization_id,
                'statutory_rule_set_id' => $ruleSet->id,
                'version' => $data['version'],
                'effective_from' => $data['effective_from'],
                'effective_until' => $data['effective_until'] ?? null,
                'jurisdiction' => $data['jurisdiction'] ?? $ruleSet->jurisdiction,
                'configuration' => $data['configuration'] ?? config('hrms.statutory.default_india_configuration', []),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->auditLogger->log($version, 'statutory_rule_version_created', [
                'rule_set_id' => $ruleSet->id,
                'version' => $version->version,
            ], $actor);

            event(StatutoryRuleChanged::forModel($ruleSet->fresh(), [
                'actor_id' => $actor->id,
                'action' => 'version_created',
                'version_id' => $version->id,
            ]));

            return $version;
        });
    }

    /**
     * Ensure an India 2026 rule pack exists for the current organization.
     */
    public function ensureDefaultIndiaRuleSet(?User $actor = null): StatutoryRuleSet
    {
        $existing = StatutoryRuleSet::query()->where('code', 'india_2026')->first();
        if ($existing) {
            return $existing->load('versions');
        }

        $actor ??= User::query()->find(auth()->id()) ?? User::query()->firstOrFail();

        return $this->createRuleSet([
            'code' => 'india_2026',
            'name' => 'India 2026',
            'jurisdiction' => 'IN',
            'description' => 'Indian statutory payroll compliance (EPF, ESI, PT, Income Tax TDS)',
            'version' => '2026.1',
            'effective_from' => '2026-01-01',
            'is_active' => true,
            'configuration' => config('hrms.statutory.default_india_configuration', []),
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  list<array<string, mixed>>  $earnings
     * @return array<string, mixed>
     */
    public function calculatePf(
        EmployeeStatutoryProfile $profile,
        array $config,
        float $gross,
        array $earnings,
        string $roundingPolicy = 'nearest',
    ): array {
        $pfConfig = $config['pf'] ?? [];

        if (! $profile->pf_eligible || ! ($pfConfig['enabled'] ?? true)) {
            return [
                'eligible' => false,
                'employee_amount' => 0.0,
                'employer_amount' => 0.0,
                'wage_base' => 0.0,
                'wage_ceiling' => (float) ($pfConfig['wage_ceiling'] ?? 0),
                'status' => 'skipped_ineligible',
            ];
        }

        $wageBase = $this->resolveWageBase(
            $earnings,
            $gross,
            $pfConfig['wage_component_codes'] ?? ['BASIC']
        );
        $ceiling = (float) ($pfConfig['wage_ceiling'] ?? 15000);
        $contributoryWage = $ceiling > 0 ? min($wageBase, $ceiling) : $wageBase;

        $employeeRate = (float) ($pfConfig['employee_contribution_percent'] ?? 12);
        $employerRate = (float) ($pfConfig['employer_contribution_percent'] ?? 12);

        $employeeAmount = $this->roundAmount($contributoryWage * ($employeeRate / 100), $roundingPolicy);
        $employerAmount = $this->roundAmount($contributoryWage * ($employerRate / 100), $roundingPolicy);

        return [
            'eligible' => true,
            'employee_amount' => $employeeAmount,
            'employer_amount' => $employerAmount,
            'wage_base' => $this->roundAmount($wageBase, $roundingPolicy),
            'contributory_wage' => $this->roundAmount($contributoryWage, $roundingPolicy),
            'wage_ceiling' => $ceiling,
            'employee_rate' => $employeeRate,
            'employer_rate' => $employerRate,
            'status' => 'calculated',
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function calculateEsi(
        EmployeeStatutoryProfile $profile,
        array $config,
        float $gross,
        string $roundingPolicy = 'nearest',
    ): array {
        $esiConfig = $config['esi'] ?? [];

        if (! $profile->esi_eligible || ! ($esiConfig['enabled'] ?? true)) {
            return [
                'eligible' => false,
                'employee_amount' => 0.0,
                'employer_amount' => 0.0,
                'wage_base' => $gross,
                'threshold' => (float) ($esiConfig['wage_threshold'] ?? 21000),
                'status' => 'skipped_ineligible',
            ];
        }

        $threshold = (float) ($esiConfig['wage_threshold'] ?? 21000);
        if ($gross > $threshold) {
            return [
                'eligible' => false,
                'employee_amount' => 0.0,
                'employer_amount' => 0.0,
                'wage_base' => $gross,
                'threshold' => $threshold,
                'status' => 'skipped_above_threshold',
            ];
        }

        $employeeRate = (float) ($esiConfig['employee_contribution_percent'] ?? 0.75);
        $employerRate = (float) ($esiConfig['employer_contribution_percent'] ?? 3.25);

        return [
            'eligible' => true,
            'employee_amount' => $this->roundAmount($gross * ($employeeRate / 100), $roundingPolicy),
            'employer_amount' => $this->roundAmount($gross * ($employerRate / 100), $roundingPolicy),
            'wage_base' => $this->roundAmount($gross, $roundingPolicy),
            'threshold' => $threshold,
            'employee_rate' => $employeeRate,
            'employer_rate' => $employerRate,
            'status' => 'calculated',
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function calculateProfessionalTax(
        EmployeeStatutoryProfile $profile,
        array $config,
        float $gross,
        int $month,
        string $roundingPolicy = 'nearest',
    ): array {
        $ptConfig = $config['professional_tax'] ?? [];

        if (! ($ptConfig['enabled'] ?? true) || blank($profile->professional_tax_state)) {
            return [
                'applicable' => false,
                'amount' => 0.0,
                'state' => $profile->professional_tax_state,
                'status' => 'skipped_no_state',
            ];
        }

        $stateCode = strtoupper((string) $profile->professional_tax_state);
        $states = $ptConfig['states'] ?? [];
        $state = $states[$stateCode] ?? null;

        if (! $state) {
            return [
                'applicable' => false,
                'amount' => 0.0,
                'state' => $stateCode,
                'status' => 'skipped_unknown_state',
            ];
        }

        $exemptionMonths = array_map('intval', $state['exemption_months'] ?? []);
        if (in_array($month, $exemptionMonths, true)) {
            return [
                'applicable' => true,
                'amount' => 0.0,
                'state' => $stateCode,
                'month' => $month,
                'status' => 'exempt_month',
                'meta' => ['exemption_months' => $exemptionMonths],
            ];
        }

        $amount = 0.0;
        foreach ($state['slabs'] ?? [] as $slab) {
            $min = (float) ($slab['min'] ?? 0);
            $max = array_key_exists('max', $slab) && $slab['max'] !== null
                ? (float) $slab['max']
                : null;

            if ($gross < $min) {
                continue;
            }
            if ($max !== null && $gross > $max) {
                continue;
            }

            $amount = (float) ($slab['amount'] ?? 0);
            break;
        }

        return [
            'applicable' => true,
            'amount' => $this->roundAmount($amount, $roundingPolicy),
            'state' => $stateCode,
            'month' => $month,
            'gross' => $this->roundAmount($gross, $roundingPolicy),
            'status' => 'calculated',
        ];
    }

    /**
     * @deprecated Prefer TdsCalculationService::calculateForPayroll via applyToPayrollCalculation.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function prepareTds(
        EmployeeStatutoryProfile $profile,
        array $config,
        float $gross,
        ?Employee $employee = null,
        ?PayrollPeriod $period = null,
        array $baseCalculation = [],
        string $roundingPolicy = 'nearest',
    ): array {
        if ($employee && $period) {
            return $this->tdsCalculationService->calculateForPayroll(
                $employee,
                $period,
                $profile,
                $baseCalculation ?: ['gross_salary' => $gross, 'snapshot' => ['earnings' => []]],
                $config['tds'] ?? $config,
                $roundingPolicy,
            );
        }

        $tdsConfig = $config['tds'] ?? $config;

        return [
            'prepared' => (bool) ($tdsConfig['enabled'] ?? true),
            'calculation' => ($tdsConfig['calculation'] ?? 'engine') === 'deferred' ? 'deferred' : 'engine',
            'amount' => 0.0,
            'tax_regime' => $profile->tax_regime,
            'pan_available' => filled($profile->pan),
            'taxable_income_snapshot' => $this->roundAmount($gross, 'nearest'),
            'status' => ($tdsConfig['calculation'] ?? 'engine') === 'deferred' ? 'placeholder' : 'pending_context',
            'engine_version' => TdsCalculationService::ENGINE_VERSION,
        ];
    }

    /**
     * @param  array<string, mixed>  $pf
     * @param  array<string, mixed>  $esi
     * @param  array<string, mixed>  $pt
     * @param  array<string, mixed>  $tds
     * @return list<array<string, mixed>>
     */
    public function buildStatutoryComponents(array $pf, array $esi, array $pt, array $tds): array
    {
        $components = [];

        $components[] = $this->statutoryLine(
            'PF_EE',
            'PF Employee',
            'deduction',
            (float) ($pf['employee_amount'] ?? 0),
            $pf['status'] ?? 'calculated',
            true,
            ['source' => 'pf', 'side' => 'employee']
        );
        $components[] = $this->statutoryLine(
            'PF_ER',
            'PF Employer',
            'employer_contribution',
            (float) ($pf['employer_amount'] ?? 0),
            $pf['status'] ?? 'calculated',
            false,
            ['source' => 'pf', 'side' => 'employer']
        );
        $components[] = $this->statutoryLine(
            'ESI_EE',
            'ESI Employee',
            'deduction',
            (float) ($esi['employee_amount'] ?? 0),
            $esi['status'] ?? 'calculated',
            true,
            ['source' => 'esi', 'side' => 'employee']
        );
        $components[] = $this->statutoryLine(
            'ESI_ER',
            'ESI Employer',
            'employer_contribution',
            (float) ($esi['employer_amount'] ?? 0),
            $esi['status'] ?? 'calculated',
            false,
            ['source' => 'esi', 'side' => 'employer']
        );
        $components[] = $this->statutoryLine(
            'PT',
            'Professional Tax',
            'deduction',
            (float) ($pt['amount'] ?? 0),
            $pt['status'] ?? 'calculated',
            true,
            ['source' => 'professional_tax', 'state' => $pt['state'] ?? null]
        );
        $components[] = $this->statutoryLine(
            'TDS',
            'Income Tax (TDS)',
            'deduction',
            (float) ($tds['amount'] ?? 0),
            $tds['status'] ?? 'calculated',
            true,
            [
                'source' => 'tds',
                'tax_regime' => $tds['tax_regime'] ?? null,
                'pan_available' => $tds['pan_available'] ?? false,
                'taxable_income_snapshot' => $tds['taxable_income_snapshot'] ?? 0,
                'annual_tax_liability' => $tds['annual_tax_liability'] ?? null,
                'monthly_tds' => $tds['monthly_tds'] ?? $tds['amount'] ?? 0,
                'calculation' => $tds['calculation'] ?? 'engine',
                'engine_version' => $tds['engine_version'] ?? TdsCalculationService::ENGINE_VERSION,
                'projection_id' => $tds['projection_id'] ?? null,
                'financial_year_code' => $tds['financial_year_code'] ?? null,
            ]
        );

        return $components;
    }

    /** @return Collection<int, StatutoryRuleSet> */
    public function listRuleSets(): Collection
    {
        return StatutoryRuleSet::query()->with('versions')->orderBy('name')->get();
    }

    /** @return Collection<int, EmployeeStatutoryProfile> */
    public function listProfiles(): Collection
    {
        return EmployeeStatutoryProfile::query()
            ->with('employee')
            ->orderByDesc('updated_at')
            ->get();
    }

    /** @return Collection<int, StatutoryComplianceError> */
    public function listComplianceErrors(?int $limit = 100): Collection
    {
        return StatutoryComplianceError::query()
            ->with(['employee', 'ruleSet'])
            ->orderByDesc('id')
            ->limit($limit ?? 100)
            ->get();
    }

    public function dashboardStats(): array
    {
        $active = $this->resolveActiveRuleSet();

        return [
            'rule_set_count' => StatutoryRuleSet::query()->count(),
            'active_rule_set_name' => $active?->name,
            'active_rule_set_jurisdiction' => $active?->jurisdiction,
            'profile_count' => EmployeeStatutoryProfile::query()->count(),
            'compliance_error_count' => StatutoryComplianceError::query()->count(),
            'open_error_count' => StatutoryComplianceError::query()->whereNull('payroll_run_id')->count(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $earnings
     * @param  list<string>  $codes
     */
    protected function resolveWageBase(array $earnings, float $gross, array $codes): float
    {
        $codes = array_map('strtoupper', $codes);
        if ($codes === [] || in_array('GROSS', $codes, true)) {
            return $gross;
        }

        $sum = 0.0;
        $matched = false;
        foreach ($earnings as $line) {
            $code = strtoupper((string) ($line['code'] ?? ''));
            if (in_array($code, $codes, true)) {
                $sum += (float) ($line['amount'] ?? 0);
                $matched = true;
            }
        }

        return $matched ? $sum : $gross;
    }

    /** @return array<string, mixed> */
    protected function statutoryLine(
        string $code,
        string $name,
        string $componentType,
        float $amount,
        string $status,
        bool $affectsNet,
        array $meta = [],
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'component_type' => $componentType,
            'amount' => $amount,
            'status' => $status,
            'affects_net' => $affectsNet,
            'meta' => $meta,
        ];
    }

    protected function defaultValidationPeriod(): PayrollPeriod
    {
        $existing = PayrollPeriod::query()
            ->whereIn('status', ['open', 'draft'])
            ->orderByDesc('start_date')
            ->first();

        if ($existing) {
            return $existing;
        }

        $start = now()->startOfMonth();

        return new PayrollPeriod([
            'organization_id' => $this->tenantContext->id(),
            'name' => $start->format('F Y'),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->endOfMonth()->toDateString(),
            'status' => 'draft',
        ]);
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
