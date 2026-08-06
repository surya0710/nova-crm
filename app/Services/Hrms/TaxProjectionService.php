<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\TaxDeclaration;
use App\Models\TaxFinancialYear;
use App\Models\TaxProjection;
use App\Models\TdsMonthlyCalculation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaxProjectionService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected IncomeTaxService $incomeTaxService,
        protected PayrollService $payrollService,
    ) {}

    /**
     * Build / refresh annual tax projection for an employee.
     */
    public function projectForEmployee(
        Employee $employee,
        TaxFinancialYear $fy,
        ?PayrollPeriod $period = null,
        ?float $currentMonthGross = null,
        string $roundingPolicy = 'nearest',
        ?User $actor = null,
    ): TaxProjection {
        $regime = $this->incomeTaxService->resolveEmployeeRegime($employee, $fy);
        $asOf = $period
            ? Carbon::parse($period->end_date)
            : Carbon::parse(min(now()->toDateString(), $fy->end_date->toDateString()));

        $paidMonths = $this->paidResultsInFy($employee, $fy);
        $paidGross = (float) $paidMonths->sum(fn (PayrollResult $r) => (float) ($r->gross_salary ?? 0));

        $tdsQuery = TdsMonthlyCalculation::query()
            ->where('employee_id', $employee->id)
            ->where('tax_financial_year_id', $fy->id);

        if ($period) {
            $periodMonth = (int) Carbon::parse($period->start_date)->month;
            $periodYear = (int) Carbon::parse($period->start_date)->year;
            $tdsQuery->where(function ($q) use ($periodMonth, $periodYear) {
                $q->where('year', '!=', $periodYear)
                    ->orWhere('month', '!=', $periodMonth);
            });
        }

        $tdsAlready = (float) $tdsQuery->sum('tds_amount');

        // Also include TDS from payroll result snapshots if monthly calc rows missing.
        if ($tdsAlready <= 0 && $paidMonths->isNotEmpty()) {
            $tdsAlready = (float) $paidMonths->sum(function (PayrollResult $r) use ($period) {
                if ($period && (int) $r->payrollRun?->payroll_period_id === (int) $period->id) {
                    return 0;
                }
                foreach ($r->snapshot['deductions'] ?? [] as $line) {
                    if (strtoupper((string) ($line['code'] ?? '')) === 'TDS') {
                        return (float) ($line['amount'] ?? 0);
                    }
                }

                return (float) ($r->snapshot['statutory']['tds']['amount'] ?? 0);
            });
        }

        $currentGross = $currentMonthGross;
        if ($currentGross === null) {
            $currentGross = $this->estimateMonthlyGross($employee, $period ?? $this->syntheticPeriod($asOf));
        }

        $monthsElapsed = $this->fyMonthIndex($fy, $asOf); // 1..12 for current month in FY
        $remainingMonths = max(1, 12 - $monthsElapsed + 1);
        // If current month already has a paid result, don't double-count it in remaining.
        $paidCount = $paidMonths->count();
        $futureMonths = max(0, 12 - $paidCount);
        if ($period && ! $paidMonths->contains(fn (PayrollResult $r) => (int) $r->payrollRun?->payroll_period_id === (int) $period->id)) {
            // Current period not yet paid — include current gross once in future projection.
            $projectedFuture = ($currentGross * max(1, $futureMonths));
        } else {
            $projectedFuture = $currentGross * $futureMonths;
        }

        $adjustmentsYtd = (float) PayrollAdjustment::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($fy) {
                $q->whereBetween('effective_date', [$fy->start_date, $fy->end_date])
                    ->orWhereNull('effective_date');
            })
            ->get()
            ->sum(function (PayrollAdjustment $adj) {
                $amount = (float) $adj->amount;
                return $adj->direction === 'deduction' ? -$amount : $amount;
            });

        $projectedGross = max(0, $paidGross + ($period && $paidCount < 12 ? $currentGross * max(0, 12 - $paidCount) : $projectedFuture));
        // Prefer simple annualization: paid YTD + current*remaining when few paid months.
        if ($paidCount === 0) {
            $projectedGross = $currentGross * 12;
        } elseif ($paidCount < 12) {
            $projectedGross = $paidGross + ($currentGross * (12 - $paidCount));
        } else {
            $projectedGross = $paidGross;
        }

        $projectedGross = max(0, $projectedGross + max(0, $adjustmentsYtd));

        $approvedDeductions = $this->approvedDeclarationTotal($employee, $fy, $regime);
        $standardDeduction = $this->incomeTaxService->standardDeduction(
            $regime,
            $fy->configuration ?: config('hrms.statutory.default_india_configuration.tds', [])
        );

        $projectedTaxable = max(0, $projectedGross - $standardDeduction - $approvedDeductions);
        $tax = $this->incomeTaxService->calculateAnnualTax($projectedTaxable, $regime, $fy, $roundingPolicy);

        $annualLiability = (float) $tax['total_tax'];
        $remainingTds = max(0, $annualLiability - $tdsAlready);
        $monthsLeft = max(1, 12 - $paidCount);
        $monthlyTds = round($remainingTds / $monthsLeft, 2);

        $breakdown = [
            'paid_gross' => round($paidGross, 2),
            'current_month_gross' => round((float) $currentGross, 2),
            'paid_months' => $paidCount,
            'remaining_months' => $monthsLeft,
            'adjustments' => round($adjustmentsYtd, 2),
            'standard_deduction' => round($standardDeduction, 2),
            'approved_declarations' => round($approvedDeductions, 2),
            'tax' => $tax,
            'engine_version' => IncomeTaxService::ENGINE_VERSION,
        ];

        $projection = TaxProjection::query()->updateOrCreate(
            [
                'organization_id' => $employee->organization_id,
                'employee_id' => $employee->id,
                'tax_financial_year_id' => $fy->id,
            ],
            [
                'regime' => $regime,
                'projected_gross' => round($projectedGross, 2),
                'projected_taxable' => round($projectedTaxable, 2),
                'projected_tax' => (float) $tax['tax_after_rebate'],
                'projected_cess' => (float) $tax['cess'],
                'projected_surcharge' => (float) $tax['surcharge'],
                'projected_rebate' => (float) $tax['rebate'],
                'annual_tax_liability' => $annualLiability,
                'tds_already_deducted' => round($tdsAlready, 2),
                'remaining_tds' => round($remainingTds, 2),
                'remaining_months' => $monthsLeft,
                'monthly_tds' => $monthlyTds,
                'breakdown' => $breakdown,
                'source' => 'system',
                'calculated_at' => now(),
            ]
        );

        $this->auditLogger->log($projection, 'tax_projection_calculated', [
            'employee_id' => $employee->id,
            'annual_tax_liability' => $annualLiability,
            'monthly_tds' => $monthlyTds,
        ], $actor);

        return $projection->fresh();
    }

    public function approvedDeclarationTotal(Employee $employee, TaxFinancialYear $fy, string $regime): float
    {
        // New regime: chapter VIA deductions generally not available (except limited NPS employer etc.)
        if ($regime === 'new') {
            return 0.0;
        }

        $declaration = TaxDeclaration::query()
            ->where('employee_id', $employee->id)
            ->where('tax_financial_year_id', $fy->id)
            ->where('status', TaxDeclaration::STATUS_VERIFIED)
            ->latest('id')
            ->first();

        if (! $declaration) {
            return 0.0;
        }

        $limits = config('hrms.statutory.default_india_configuration.tds.section_limits', []);
        $total = 0.0;
        $sectionTotals = [];

        foreach ($declaration->items as $item) {
            $amount = (float) ($item->approved_amount ?? $item->declared_amount);
            $section = $item->section ?: $item->category;
            $sectionTotals[$section] = ($sectionTotals[$section] ?? 0) + $amount;
        }

        foreach ($sectionTotals as $section => $amount) {
            $limit = $limits[$section] ?? $limits[$this->mapSectionKey($section)] ?? null;
            $total += $limit !== null ? min($amount, (float) $limit) : $amount;
        }

        return round($total, 2);
    }

    protected function mapSectionKey(string $section): string
    {
        return match (strtoupper($section)) {
            '80C' => '80C',
            '80CCD', 'NPS' => '80CCD',
            '80D' => '80D',
            '24', 'HOME_LOAN_INTEREST' => 'home_loan_interest',
            '80E', 'EDUCATION_LOAN' => 'education_loan',
            default => strtolower($section),
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, PayrollResult>
     */
    protected function paidResultsInFy(Employee $employee, TaxFinancialYear $fy)
    {
        return PayrollResult::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', function ($q) use ($fy) {
                $q->whereIn('status', ['calculated', 'approved', 'published', 'paid'])
                    ->whereHas('period', function ($pq) use ($fy) {
                        $pq->whereDate('start_date', '>=', $fy->start_date)
                            ->whereDate('end_date', '<=', $fy->end_date);
                    });
            })
            ->with(['payrollRun.period'])
            ->get()
            ->unique(fn (PayrollResult $r) => $r->payrollRun?->payroll_period_id);
    }

    protected function estimateMonthlyGross(Employee $employee, PayrollPeriod $period): float
    {
        try {
            $context = $this->payrollService->resolveCalculationContext($employee, $period);
            $components = $context['components'] ?? [];
            $gross = 0.0;
            foreach ($components as $component) {
                if (($component['component_type'] ?? '') === 'earning') {
                    $gross += (float) ($component['amount'] ?? 0);
                }
            }

            return round($gross, 2);
        } catch (\Throwable) {
            $assignment = $this->payrollService->getActiveSalaryAssignment($employee, Carbon::parse($period->end_date));
            if ($assignment?->annual_ctc) {
                return round(((float) $assignment->annual_ctc) / 12, 2);
            }

            return 0.0;
        }
    }

    protected function fyMonthIndex(TaxFinancialYear $fy, Carbon $asOf): int
    {
        $start = Carbon::parse($fy->start_date)->startOfMonth();
        $months = ((int) $asOf->format('Y') * 12 + (int) $asOf->format('n'))
            - ((int) $start->format('Y') * 12 + (int) $start->format('n'));

        return max(1, min(12, $months + 1));
    }

    protected function syntheticPeriod(Carbon $asOf): PayrollPeriod
    {
        return new PayrollPeriod([
            'organization_id' => $this->tenantContext->id(),
            'name' => $asOf->format('F Y'),
            'start_date' => $asOf->copy()->startOfMonth()->toDateString(),
            'end_date' => $asOf->copy()->endOfMonth()->toDateString(),
            'status' => 'draft',
        ]);
    }
}
