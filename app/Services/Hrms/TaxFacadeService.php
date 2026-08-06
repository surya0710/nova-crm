<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\Form16Record;
use App\Models\PayrollPeriod;
use App\Models\TaxDeclaration;
use App\Models\TaxFinancialYear;
use App\Models\TaxProof;
use App\Models\TaxProjection;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Orchestrates Indian income tax workflows. Delegates to dedicated services.
 */
class TaxFacadeService
{
    public function __construct(
        protected IncomeTaxService $incomeTaxService,
        protected TaxProjectionService $taxProjectionService,
        protected TdsCalculationService $tdsCalculationService,
        protected InvestmentDeclarationService $declarationService,
        protected TaxProofService $proofService,
        protected Form16Service $form16Service,
        protected TaxDashboardService $dashboardService,
        protected TaxReportService $reportService,
    ) {}

    public function ensureFinancialYear(?User $actor = null): TaxFinancialYear
    {
        return $this->incomeTaxService->ensureDefaultFinancialYear($actor);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createFinancialYear(array $attributes, ?User $actor = null, bool $seedSlabs = true): TaxFinancialYear
    {
        return $this->incomeTaxService->createFinancialYear($attributes, $actor, $seedSlabs);
    }

    public function activateFinancialYear(TaxFinancialYear $fy, ?User $actor = null): TaxFinancialYear
    {
        return $this->incomeTaxService->activateFinancialYear($fy, $actor);
    }

    /**
     * @param  array{regime: string, effective_from?: string, notes?: string|null}  $data
     */
    public function selectRegime(Employee $employee, TaxFinancialYear $fy, array $data, ?User $actor = null)
    {
        return $this->incomeTaxService->selectRegime($employee, $fy, $data, $actor);
    }

    public function projectEmployee(
        Employee $employee,
        TaxFinancialYear $fy,
        ?PayrollPeriod $period = null,
        ?float $currentMonthGross = null,
        ?User $actor = null,
    ): TaxProjection {
        return $this->taxProjectionService->projectForEmployee(
            $employee,
            $fy,
            $period,
            $currentMonthGross,
            'nearest',
            $actor
        );
    }

    /**
     * Preview monthly TDS without persisting payroll integration artifacts.
     *
     * @return array<string, mixed>
     */
    public function calculateTds(
        Employee $employee,
        TaxFinancialYear $fy,
        ?PayrollPeriod $period = null,
        ?float $currentMonthGross = null,
        ?User $actor = null,
    ): array {
        $projection = $this->taxProjectionService->projectForEmployee(
            $employee,
            $fy,
            $period,
            $currentMonthGross,
            'nearest',
            $actor
        );

        return [
            'employee_id' => $employee->id,
            'financial_year_id' => $fy->id,
            'regime' => $projection->regime,
            'projected_gross' => (float) $projection->projected_gross,
            'projected_taxable' => (float) $projection->projected_taxable,
            'annual_tax_liability' => (float) $projection->annual_tax_liability,
            'tds_already_deducted' => (float) $projection->tds_already_deducted,
            'remaining_tds' => (float) $projection->remaining_tds,
            'remaining_months' => (int) $projection->remaining_months,
            'monthly_tds' => (float) $projection->monthly_tds,
            'projection_id' => $projection->id,
            'breakdown' => $projection->breakdown,
            'engine_version' => IncomeTaxService::ENGINE_VERSION,
        ];
    }

    /**
     * @param  list<array{category: string, section?: string|null, label: string, declared_amount: float|int|string}>  $items
     */
    public function createDeclaration(
        Employee $employee,
        TaxFinancialYear $fy,
        array $items,
        ?User $actor = null,
    ): TaxDeclaration {
        return $this->declarationService->createDraft($employee, $fy, $items, $actor);
    }

    public function submitDeclaration(TaxDeclaration $declaration, ?User $actor = null): TaxDeclaration
    {
        return $this->declarationService->submit($declaration, $actor);
    }

    public function verifyDeclaration(TaxDeclaration $declaration, ?User $actor = null, ?string $comments = null): TaxDeclaration
    {
        return $this->declarationService->verify($declaration, $actor, $comments);
    }

    public function rejectDeclaration(TaxDeclaration $declaration, string $reason, ?User $actor = null): TaxDeclaration
    {
        return $this->declarationService->reject($declaration, $reason, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function uploadProof(
        TaxDeclaration $declaration,
        array $data,
        ?UploadedFile $file,
        ?User $actor = null,
    ): TaxProof {
        return $this->proofService->upload($declaration, $data, $file, $actor);
    }

    public function verifyProof(
        TaxProof $proof,
        float $approvedAmount,
        ?string $comments = null,
        ?User $actor = null,
    ): TaxProof {
        return $this->proofService->verify($proof, $approvedAmount, $comments, $actor);
    }

    public function rejectProof(TaxProof $proof, string $comments, ?User $actor = null): TaxProof
    {
        return $this->proofService->reject($proof, $comments, $actor);
    }

    public function generateForm16(Employee $employee, TaxFinancialYear $fy, ?User $actor = null): Form16Record
    {
        return $this->form16Service->generate($employee, $fy, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return $this->dashboardService->widgets();
    }

    /**
     * @return array{headers: list<string>, data: list<array<string, mixed>>}
     */
    public function report(string $type, ?int $financialYearId = null): array
    {
        return $this->reportService->report($type, $financialYearId);
    }

    /**
     * @return array{path: string, filename: string, disk: string}
     */
    public function exportReport(string $type, string $format, ?int $financialYearId = null): array
    {
        return $this->reportService->export($type, $format, $financialYearId);
    }
}
