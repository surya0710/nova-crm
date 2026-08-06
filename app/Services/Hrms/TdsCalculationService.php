<?php

namespace App\Services\Hrms;

use App\Events\TdsCalculated;
use App\Models\Employee;
use App\Models\EmployeeStatutoryProfile;
use App\Models\PayrollPeriod;
use App\Models\TaxFinancialYear;
use App\Models\TdsMonthlyCalculation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Monthly TDS calculation engine. Consumed by StatutoryComplianceService —
 * PayrollCalculationService must not duplicate this math.
 */
class TdsCalculationService
{
    public const ENGINE_VERSION = '10.3.7';

    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected IncomeTaxService $incomeTaxService,
        protected TaxProjectionService $taxProjectionService,
    ) {}

    /**
     * Calculate monthly TDS for payroll integration.
     *
     * @param  array<string, mixed>  $baseCalculation
     * @param  array<string, mixed>  $tdsConfig  from statutory rule version
     * @return array<string, mixed>
     */
    public function calculateForPayroll(
        Employee $employee,
        PayrollPeriod $period,
        EmployeeStatutoryProfile $profile,
        array $baseCalculation,
        array $tdsConfig = [],
        string $roundingPolicy = 'nearest',
        ?User $actor = null,
    ): array {
        $enabled = (bool) ($tdsConfig['enabled'] ?? true);
        $gross = (float) ($baseCalculation['gross_salary'] ?? 0);

        if (! $enabled) {
            return $this->emptyResult($profile, $gross, 'disabled');
        }

        $fy = $this->incomeTaxService->resolveFinancialYear(Carbon::parse($period->end_date));
        if (! $fy) {
            $fy = $this->incomeTaxService->ensureDefaultFinancialYear($actor);
        }

        // Prefer FY-stored configuration when present; merge statutory pack overrides.
        $config = array_replace_recursive(
            config('hrms.statutory.default_india_configuration.tds', []),
            $fy->configuration ?? [],
            $tdsConfig
        );

        if (($config['calculation'] ?? 'engine') === 'deferred') {
            return [
                'prepared' => true,
                'calculation' => 'deferred',
                'amount' => 0.0,
                'tax_regime' => $profile->tax_regime,
                'pan_available' => filled($profile->pan),
                'taxable_income_snapshot' => round($gross, 2),
                'status' => 'placeholder',
                'engine_version' => self::ENGINE_VERSION,
            ];
        }

        $projection = $this->taxProjectionService->projectForEmployee(
            $employee,
            $fy,
            $period,
            $gross,
            $roundingPolicy,
            $actor
        );

        $amount = max(0, (float) $projection->monthly_tds);
        $month = (int) Carbon::parse($period->start_date)->month;
        $year = (int) Carbon::parse($period->start_date)->year;

        $record = TdsMonthlyCalculation::query()->updateOrCreate(
            [
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'tax_financial_year_id' => $fy->id,
                'payroll_period_id' => $period->id,
                'tax_projection_id' => $projection->id,
                'regime' => $projection->regime,
                'gross_salary' => round($gross, 2),
                'taxable_income_annual' => (float) $projection->projected_taxable,
                'annual_tax_liability' => (float) $projection->annual_tax_liability,
                'tds_ytd' => round(((float) $projection->tds_already_deducted) + $amount, 2),
                'tds_amount' => $amount,
                'cess_amount' => (float) $projection->projected_cess,
                'surcharge_amount' => (float) $projection->projected_surcharge,
                'rebate_amount' => (float) $projection->projected_rebate,
                'breakdown' => [
                    'projection_id' => $projection->id,
                    'monthly_tds' => $amount,
                    'remaining_tds' => (float) $projection->remaining_tds,
                    'remaining_months' => (int) $projection->remaining_months,
                    'tax' => $projection->breakdown['tax'] ?? [],
                ],
                'status' => 'calculated',
                'calculated_at' => now(),
            ]
        );

        $this->auditLogger->log($record, 'tds_calculated', [
            'employee_id' => $employee->id,
            'amount' => $amount,
            'regime' => $projection->regime,
            'period_id' => $period->id,
        ], $actor);

        event(TdsCalculated::forModel($record, [
            'employee_id' => $employee->id,
            'amount' => $amount,
            'financial_year_id' => $fy->id,
        ]));

        return [
            'prepared' => true,
            'calculation' => 'engine',
            'amount' => $amount,
            'tax_regime' => $projection->regime,
            'pan_available' => filled($profile->pan),
            'taxable_income_snapshot' => (float) $projection->projected_taxable,
            'annual_tax_liability' => (float) $projection->annual_tax_liability,
            'tds_already_deducted' => (float) $projection->tds_already_deducted,
            'remaining_months' => (int) $projection->remaining_months,
            'monthly_tds' => $amount,
            'projection_id' => $projection->id,
            'tds_monthly_calculation_id' => $record->id,
            'cess' => (float) $projection->projected_cess,
            'surcharge' => (float) $projection->projected_surcharge,
            'rebate' => (float) $projection->projected_rebate,
            'status' => 'calculated',
            'engine_version' => self::ENGINE_VERSION,
            'financial_year_id' => $fy->id,
            'financial_year_code' => $fy->code,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyResult(EmployeeStatutoryProfile $profile, float $gross, string $status): array
    {
        return [
            'prepared' => false,
            'calculation' => 'engine',
            'amount' => 0.0,
            'tax_regime' => $profile->tax_regime,
            'pan_available' => filled($profile->pan),
            'taxable_income_snapshot' => round($gross, 2),
            'status' => $status,
            'engine_version' => self::ENGINE_VERSION,
        ];
    }
}
