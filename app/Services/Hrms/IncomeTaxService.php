<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeStatutoryProfile;
use App\Models\EmployeeTaxRegime;
use App\Models\TaxFinancialYear;
use App\Models\TaxSlab;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncomeTaxService
{
    public const ENGINE_VERSION = '10.3.7';

    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Ensure the organization has an active financial year with slabs.
     */
    public function ensureDefaultFinancialYear(?User $actor = null): TaxFinancialYear
    {
        $active = TaxFinancialYear::query()->where('is_active', true)->first();
        if ($active) {
            return $active;
        }

        $defaults = config('hrms.income_tax.default_financial_year', []);
        $asOf = now();

        // Prefer matching the calendar date into an FY window when seeding.
        if ($asOf->month < 4) {
            $startYear = $asOf->year - 1;
        } else {
            $startYear = $asOf->year;
        }

        $code = sprintf('FY%d-%02d', $startYear, ($startYear + 1) % 100);
        $assessmentYear = sprintf('AY%d-%02d', $startYear + 1, ($startYear + 2) % 100);
        $startDate = sprintf('%d-04-01', $startYear);
        $endDate = sprintf('%d-03-31', $startYear + 1);

        // Use configured defaults only when they cover the current date.
        $configStart = $defaults['start_date'] ?? null;
        $configEnd = $defaults['end_date'] ?? null;
        if ($configStart && $configEnd
            && $asOf->betweenIncluded(Carbon::parse($configStart), Carbon::parse($configEnd))) {
            $code = $defaults['code'] ?? $code;
            $assessmentYear = $defaults['assessment_year'] ?? $assessmentYear;
            $startDate = $configStart;
            $endDate = $configEnd;
        }

        return $this->createFinancialYear([
            'code' => $code,
            'label' => $defaults['label'] ?? "Financial Year {$startYear}-".substr((string) ($startYear + 1), -2),
            'assessment_year' => $assessmentYear,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'default_regime' => $defaults['default_regime'] ?? 'new',
            'is_active' => true,
            'configuration' => config('hrms.statutory.default_india_configuration.tds', []),
        ], $actor, seedSlabs: true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createFinancialYear(array $attributes, ?User $actor = null, bool $seedSlabs = true): TaxFinancialYear
    {
        return DB::transaction(function () use ($attributes, $actor, $seedSlabs) {
            if (! empty($attributes['is_active'])) {
                TaxFinancialYear::query()->where('is_active', true)->update(['is_active' => false]);
            }

            $fy = TaxFinancialYear::query()->create([
                'code' => $attributes['code'],
                'label' => $attributes['label'],
                'assessment_year' => $attributes['assessment_year'],
                'start_date' => $attributes['start_date'],
                'end_date' => $attributes['end_date'],
                'default_regime' => $attributes['default_regime'] ?? 'new',
                'is_active' => (bool) ($attributes['is_active'] ?? false),
                'version' => (int) ($attributes['version'] ?? 1),
                'configuration' => $attributes['configuration'] ?? config('hrms.statutory.default_india_configuration.tds', []),
                'custom_fields' => $attributes['custom_fields'] ?? null,
                'created_by' => $actor?->id,
            ]);

            if ($seedSlabs) {
                $this->seedSlabsFromConfiguration($fy);
            }

            $this->auditLogger->log($fy, 'tax_financial_year_created', [
                'code' => $fy->code,
                'version' => $fy->version,
            ], $actor);

            return $fy->fresh(['slabs']);
        });
    }

    public function activateFinancialYear(TaxFinancialYear $fy, ?User $actor = null): TaxFinancialYear
    {
        return DB::transaction(function () use ($fy, $actor) {
            TaxFinancialYear::query()->where('is_active', true)->whereKeyNot($fy->id)->update(['is_active' => false]);
            $fy->update(['is_active' => true]);

            $this->auditLogger->log($fy, 'tax_financial_year_activated', [
                'code' => $fy->code,
            ], $actor);

            return $fy->fresh();
        });
    }

    public function resolveFinancialYear(?CarbonInterface $asOf = null): ?TaxFinancialYear
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();

        $byDate = TaxFinancialYear::query()
            ->whereDate('start_date', '<=', $asOf)
            ->whereDate('end_date', '>=', $asOf)
            ->orderByDesc('version')
            ->first();

        if ($byDate) {
            return $byDate;
        }

        return TaxFinancialYear::query()->where('is_active', true)->orderByDesc('version')->first();
    }

    public function seedSlabsFromConfiguration(TaxFinancialYear $fy, ?array $tdsConfig = null): void
    {
        $tdsConfig ??= $fy->configuration ?: config('hrms.statutory.default_india_configuration.tds', []);
        $cess = (float) ($tdsConfig['cess_percent'] ?? 4);
        $slabsByRegime = $tdsConfig['slabs'] ?? [];

        TaxSlab::query()->where('tax_financial_year_id', $fy->id)->delete();

        foreach (['old', 'new'] as $regime) {
            $sort = 0;
            foreach ($slabsByRegime[$regime] ?? [] as $slab) {
                TaxSlab::query()->create([
                    'organization_id' => $fy->organization_id,
                    'tax_financial_year_id' => $fy->id,
                    'regime' => $regime,
                    'min_income' => $slab['min'] ?? 0,
                    'max_income' => $slab['max'] ?? null,
                    'tax_percent' => $slab['percent'] ?? 0,
                    'surcharge_percent' => 0,
                    'cess_percent' => $cess,
                    'sort_order' => $sort++,
                    'meta' => null,
                ]);
            }
        }
    }

    /**
     * @return Collection<int, TaxSlab>
     */
    public function slabsForRegime(TaxFinancialYear $fy, string $regime): Collection
    {
        $slabs = $fy->slabs()->where('regime', $regime)->orderBy('sort_order')->get();

        if ($slabs->isNotEmpty()) {
            return $slabs;
        }

        // Fallback to config when slabs have not been seeded for this FY.
        $configSlabs = config("hrms.statutory.default_india_configuration.tds.slabs.{$regime}", []);
        $cess = (float) config('hrms.statutory.default_india_configuration.tds.cess_percent', 4);

        return collect($configSlabs)->values()->map(function (array $slab, int $index) use ($fy, $regime, $cess) {
            return new TaxSlab([
                'organization_id' => $fy->organization_id,
                'tax_financial_year_id' => $fy->id,
                'regime' => $regime,
                'min_income' => $slab['min'] ?? 0,
                'max_income' => $slab['max'] ?? null,
                'tax_percent' => $slab['percent'] ?? 0,
                'surcharge_percent' => 0,
                'cess_percent' => $cess,
                'sort_order' => $index,
            ]);
        });
    }

    public function resolveEmployeeRegime(Employee $employee, TaxFinancialYear $fy): string
    {
        $history = EmployeeTaxRegime::query()
            ->where('employee_id', $employee->id)
            ->where('tax_financial_year_id', $fy->id)
            ->where('status', 'active')
            ->orderByDesc('effective_from')
            ->first();

        if ($history) {
            return $history->regime;
        }

        $profile = EmployeeStatutoryProfile::query()->where('employee_id', $employee->id)->first();

        return $profile?->tax_regime ?: ($fy->default_regime ?: 'new');
    }

    /**
     * @param  array{regime: string, effective_from?: string, notes?: string|null}  $data
     */
    public function selectRegime(Employee $employee, TaxFinancialYear $fy, array $data, ?User $actor = null): EmployeeTaxRegime
    {
        return DB::transaction(function () use ($employee, $fy, $data, $actor) {
            $regime = $data['regime'];
            $effectiveFrom = Carbon::parse($data['effective_from'] ?? $fy->start_date)->toDateString();

            EmployeeTaxRegime::query()
                ->where('employee_id', $employee->id)
                ->where('tax_financial_year_id', $fy->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'superseded',
                    'effective_until' => Carbon::parse($effectiveFrom)->subDay()->toDateString(),
                ]);

            $record = EmployeeTaxRegime::query()->create([
                'employee_id' => $employee->id,
                'tax_financial_year_id' => $fy->id,
                'regime' => $regime,
                'status' => 'active',
                'effective_from' => $effectiveFrom,
                'notes' => $data['notes'] ?? null,
                'selected_by' => $actor?->id,
                'selected_at' => now(),
            ]);

            EmployeeStatutoryProfile::query()->updateOrCreate(
                [
                    'organization_id' => $employee->organization_id,
                    'employee_id' => $employee->id,
                ],
                ['tax_regime' => $regime]
            );

            $this->auditLogger->log($record, 'tax_regime_changed', [
                'employee_id' => $employee->id,
                'regime' => $regime,
                'financial_year_id' => $fy->id,
            ], $actor);

            return $record;
        });
    }

    /**
     * Compute annual income tax for a taxable income under a regime.
     *
     * @return array{
     *   taxable_income: float,
     *   tax_before_rebate: float,
     *   rebate: float,
     *   tax_after_rebate: float,
     *   surcharge: float,
     *   cess: float,
     *   total_tax: float,
     *   slab_breakdown: list<array<string, mixed>>,
     *   regime: string,
     *   engine_version: string
     * }
     */
    public function calculateAnnualTax(
        float $taxableIncome,
        string $regime,
        TaxFinancialYear $fy,
        string $roundingPolicy = 'nearest',
    ): array {
        $taxableIncome = max(0, $taxableIncome);
        $slabs = $this->slabsForRegime($fy, $regime);
        $tdsConfig = $fy->configuration ?: config('hrms.statutory.default_india_configuration.tds', []);

        $taxBeforeRebate = 0.0;
        $slabBreakdown = [];
        $previousCap = 0.0;

        foreach ($slabs as $slab) {
            $min = (float) $slab->min_income;
            $max = $slab->max_income !== null ? (float) $slab->max_income : null;
            $percent = (float) $slab->tax_percent;
            $cap = $max ?? $taxableIncome;

            // Income taxed in this slab = overlap of taxable income above previous slab ceiling.
            $inSlab = max(0, min($taxableIncome, $cap) - $previousCap);
            if ($inSlab <= 0 && $taxableIncome <= $previousCap) {
                $previousCap = $max ?? $previousCap;
                continue;
            }

            if ($inSlab > 0) {
                $tax = $inSlab * ($percent / 100);
                $taxBeforeRebate += $tax;
                $slabBreakdown[] = [
                    'min' => $min,
                    'max' => $max,
                    'percent' => $percent,
                    'taxable_in_slab' => $this->roundAmount($inSlab, $roundingPolicy),
                    'tax' => $this->roundAmount($tax, $roundingPolicy),
                ];
            }

            $previousCap = $max ?? max($previousCap, $taxableIncome);
        }

        $taxBeforeRebate = $this->roundAmount($taxBeforeRebate, $roundingPolicy);

        $rebateConfig = $tdsConfig['rebate_87a'][$regime]
            ?? config("hrms.statutory.default_india_configuration.tds.rebate_87a.{$regime}", []);
        $rebate = 0.0;
        if ($taxableIncome <= (float) ($rebateConfig['max_taxable_income'] ?? 0)) {
            $rebate = min($taxBeforeRebate, (float) ($rebateConfig['max_rebate'] ?? 0));
        }
        $rebate = $this->roundAmount($rebate, $roundingPolicy);
        $taxAfterRebate = max(0, $taxBeforeRebate - $rebate);

        $surcharge = $this->calculateSurcharge($taxableIncome, $taxAfterRebate, $tdsConfig, $roundingPolicy);
        $cessPercent = (float) ($tdsConfig['cess_percent'] ?? 4);
        $cess = $this->roundAmount(($taxAfterRebate + $surcharge) * ($cessPercent / 100), $roundingPolicy);
        $totalTax = $this->roundAmount($taxAfterRebate + $surcharge + $cess, $roundingPolicy);

        return [
            'taxable_income' => $this->roundAmount($taxableIncome, $roundingPolicy),
            'tax_before_rebate' => $taxBeforeRebate,
            'rebate' => $rebate,
            'tax_after_rebate' => $this->roundAmount($taxAfterRebate, $roundingPolicy),
            'surcharge' => $surcharge,
            'cess' => $cess,
            'total_tax' => $totalTax,
            'slab_breakdown' => $slabBreakdown,
            'regime' => $regime,
            'engine_version' => self::ENGINE_VERSION,
        ];
    }

    /**
     * @param  array<string, mixed>  $tdsConfig
     */
    protected function calculateSurcharge(
        float $taxableIncome,
        float $taxAfterRebate,
        array $tdsConfig,
        string $roundingPolicy,
    ): float {
        if ($taxAfterRebate <= 0) {
            return 0.0;
        }

        $slabs = $tdsConfig['surcharge_slabs']
            ?? config('hrms.statutory.default_india_configuration.tds.surcharge_slabs', []);

        $percent = 0.0;
        foreach ($slabs as $slab) {
            $min = (float) ($slab['min'] ?? 0);
            $max = array_key_exists('max', $slab) ? $slab['max'] : null;
            if ($taxableIncome >= $min && ($max === null || $taxableIncome <= (float) $max)) {
                $percent = (float) ($slab['percent'] ?? 0);
            }
        }

        return $this->roundAmount($taxAfterRebate * ($percent / 100), $roundingPolicy);
    }

    public function standardDeduction(string $regime, ?array $tdsConfig = null): float
    {
        $tdsConfig ??= config('hrms.statutory.default_india_configuration.tds', []);

        return (float) ($tdsConfig['standard_deduction'][$regime]
            ?? config("hrms.statutory.default_india_configuration.tds.standard_deduction.{$regime}", 0));
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
}
