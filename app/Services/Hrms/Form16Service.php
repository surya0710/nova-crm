<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeStatutoryProfile;
use App\Models\Form16Record;
use App\Models\Organization;
use App\Models\TaxDeclaration;
use App\Models\TaxFinancialYear;
use App\Models\TaxProjection;
use App\Models\TdsMonthlyCalculation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class Form16Service
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
        protected IncomeTaxService $incomeTaxService,
    ) {}

    public function generate(Employee $employee, TaxFinancialYear $fy, ?User $actor = null): Form16Record
    {
        return DB::transaction(function () use ($employee, $fy, $actor): Form16Record {
            $employee->loadMissing(['statutoryProfile', 'department', 'designation']);
            $organization = Organization::query()->findOrFail($employee->organization_id);

            $projection = TaxProjection::query()
                ->where('employee_id', $employee->id)
                ->where('tax_financial_year_id', $fy->id)
                ->first();

            $tdsRows = TdsMonthlyCalculation::query()
                ->where('employee_id', $employee->id)
                ->where('tax_financial_year_id', $fy->id)
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            $declaration = TaxDeclaration::query()
                ->where('employee_id', $employee->id)
                ->where('tax_financial_year_id', $fy->id)
                ->where('status', TaxDeclaration::STATUS_VERIFIED)
                ->latest('id')
                ->with('items')
                ->first();

            $profile = $employee->statutoryProfile ?? EmployeeStatutoryProfile::query()
                ->where('employee_id', $employee->id)
                ->first();

            $regime = $projection?->regime ?? $this->incomeTaxService->resolveEmployeeRegime($employee, $fy);
            $employerDetails = $this->buildEmployerDetails($organization);
            $employeeDetails = $this->buildEmployeeDetails($employee, $profile);
            $salaryBreakup = $this->buildSalaryBreakup($projection, $tdsRows);
            $deductions = $this->buildDeductions($declaration, $projection, $regime, $fy);
            $taxPaid = $this->buildTaxPaid($tdsRows, $projection);
            $partA = $this->buildPartA($organization, $employee, $profile, $fy, $taxPaid);
            $partB = $this->buildPartB($salaryBreakup, $deductions, $taxPaid, $projection, $regime);

            $formNumber = sprintf('FORM16-%s-%d', $fy->code, $employee->id);

            $record = Form16Record::query()->updateOrCreate(
                [
                    'organization_id' => $employee->organization_id,
                    'employee_id' => $employee->id,
                    'tax_financial_year_id' => $fy->id,
                ],
                [
                    'form_number' => $formNumber,
                    'status' => 'generated',
                    'part_a' => $partA,
                    'part_b' => $partB,
                    'employer_details' => $employerDetails,
                    'employee_details' => $employeeDetails,
                    'salary_breakup' => $salaryBreakup,
                    'deductions' => $deductions,
                    'tax_paid' => $taxPaid,
                    'generated_by' => $actor?->id,
                    'generated_at' => now(),
                ]
            );

            $this->auditLogger->log($record, 'form16_generated', [
                'employee_id' => $employee->id,
                'financial_year_id' => $fy->id,
                'form_number' => $formNumber,
            ], $actor);

            $this->notifyEmployee($employee, $record);

            return $record->fresh(['employee', 'financialYear']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildEmployerDetails(Organization $organization): array
    {
        return [
            'name' => $organization->name,
            'tax_name' => $organization->tax_name,
            'tax_number' => $organization->tax_number,
            'address' => trim(implode(', ', array_filter([
                $organization->address_line_1,
                $organization->address_line_2,
                $organization->city,
                $organization->state,
                $organization->postal_code,
                $organization->country,
            ]))),
            'email' => $organization->email,
            'phone' => $organization->phone,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildEmployeeDetails(Employee $employee, ?EmployeeStatutoryProfile $profile): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'name' => $employee->full_name,
            'pan' => $profile?->pan,
            'department' => $employee->department?->name,
            'designation' => $employee->designation?->name,
            'joining_date' => $employee->joining_date?->toDateString(),
            'address' => trim(implode(', ', array_filter([
                $employee->address_line_1,
                $employee->address_line_2,
                $employee->city,
                $employee->state,
                $employee->postal_code,
                $employee->country,
            ]))),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TdsMonthlyCalculation>  $tdsRows
     * @return array<string, mixed>
     */
    protected function buildSalaryBreakup(?TaxProjection $projection, $tdsRows): array
    {
        $paidGross = round((float) $tdsRows->sum('gross_salary'), 2);
        $projectedGross = round((float) ($projection?->projected_gross ?? $paidGross), 2);

        return [
            'gross_salary_paid' => $paidGross,
            'projected_gross' => $projectedGross,
            'projected_taxable' => round((float) ($projection?->projected_taxable ?? 0), 2),
            'monthly_breakdown' => $tdsRows->map(fn (TdsMonthlyCalculation $row) => [
                'year' => $row->year,
                'month' => $row->month,
                'gross_salary' => (float) $row->gross_salary,
                'tds_amount' => (float) $row->tds_amount,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDeductions(
        ?TaxDeclaration $declaration,
        ?TaxProjection $projection,
        string $regime,
        TaxFinancialYear $fy,
    ): array {
        $standardDeduction = $this->incomeTaxService->standardDeduction(
            $regime,
            $fy->configuration ?: config('hrms.statutory.default_india_configuration.tds', [])
        );

        $items = $declaration?->items->map(fn ($item) => [
            'category' => $item->category,
            'section' => $item->section,
            'label' => $item->label,
            'declared_amount' => (float) $item->declared_amount,
            'approved_amount' => (float) ($item->approved_amount ?? $item->declared_amount),
        ])->values()->all() ?? [];

        return [
            'regime' => $regime,
            'standard_deduction' => round($standardDeduction, 2),
            'chapter_via' => $items,
            'approved_total' => round((float) ($declaration?->approved_total ?? 0), 2),
            'breakdown' => $projection?->breakdown ?? [],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TdsMonthlyCalculation>  $tdsRows
     * @return array<string, mixed>
     */
    protected function buildTaxPaid($tdsRows, ?TaxProjection $projection): array
    {
        $tdsTotal = round((float) $tdsRows->sum('tds_amount'), 2);
        $cessTotal = round((float) $tdsRows->sum('cess_amount'), 2);
        $surchargeTotal = round((float) $tdsRows->sum('surcharge_amount'), 0);

        return [
            'tds_deducted' => $tdsTotal,
            'cess' => $cessTotal,
            'surcharge' => $surchargeTotal,
            'rebate' => round((float) ($projection?->projected_rebate ?? 0), 2),
            'annual_tax_liability' => round((float) ($projection?->annual_tax_liability ?? $tdsTotal), 2),
            'monthly' => $tdsRows->map(fn (TdsMonthlyCalculation $row) => [
                'year' => $row->year,
                'month' => $row->month,
                'tds_amount' => (float) $row->tds_amount,
                'tds_ytd' => (float) $row->tds_ytd,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPartA(
        Organization $organization,
        Employee $employee,
        ?EmployeeStatutoryProfile $profile,
        TaxFinancialYear $fy,
        array $taxPaid,
    ): array {
        return [
            'certificate_for' => $fy->assessment_year,
            'financial_year' => $fy->code,
            'employer' => [
                'name' => $organization->name,
                'tan' => $organization->tax_number,
            ],
            'employee' => [
                'name' => $employee->full_name,
                'pan' => $profile?->pan,
            ],
            'tax_deducted' => $taxPaid['tds_deducted'] ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPartB(
        array $salaryBreakup,
        array $deductions,
        array $taxPaid,
        ?TaxProjection $projection,
        string $regime,
    ): array {
        return [
            'regime' => $regime,
            'gross_salary' => $salaryBreakup['projected_gross'] ?? 0,
            'standard_deduction' => $deductions['standard_deduction'] ?? 0,
            'chapter_via_deductions' => $deductions['approved_total'] ?? 0,
            'taxable_income' => $salaryBreakup['projected_taxable'] ?? 0,
            'tax_payable' => $taxPaid['annual_tax_liability'] ?? 0,
            'tax_deducted' => $taxPaid['tds_deducted'] ?? 0,
            'tax_payable_refundable' => round(
                (float) ($taxPaid['annual_tax_liability'] ?? 0) - (float) ($taxPaid['tds_deducted'] ?? 0),
                2
            ),
            'projection' => $projection ? [
                'projected_tax' => (float) $projection->projected_tax,
                'projected_cess' => (float) $projection->projected_cess,
                'projected_surcharge' => (float) $projection->projected_surcharge,
            ] : null,
        ];
    }

    protected function notifyEmployee(Employee $employee, Form16Record $record): void
    {
        if (! $employee->user_id) {
            return;
        }

        try {
            $this->notificationService->send(
                $employee->organization_id,
                (int) $employee->user_id,
                __('Form 16 generated'),
                __('Your Form 16 :number for :fy is ready to download.', [
                    'number' => $record->form_number,
                    'fy' => $record->financialYear?->code ?? '',
                ]),
                '/hrms/ess/tax/form16/'.$record->id
            );
        } catch (\Throwable) {
            // best-effort
        }
    }
}
