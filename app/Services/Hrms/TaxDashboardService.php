<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeTaxRegime;
use App\Models\TaxDeclaration;
use App\Models\TaxProjection;
use App\Models\TaxProof;
use App\Models\TdsMonthlyCalculation;

class TaxDashboardService
{
    public function __construct(
        protected IncomeTaxService $incomeTaxService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function widgets(): array
    {
        $fy = $this->incomeTaxService->resolveFinancialYear();
        $fyId = $fy?->id;

        $pendingDeclarations = TaxDeclaration::query()
            ->where('status', TaxDeclaration::STATUS_SUBMITTED)
            ->when($fyId, fn ($q) => $q->where('tax_financial_year_id', $fyId))
            ->count();

        $pendingProofVerification = TaxProof::query()
            ->whereIn('status', [TaxProof::STATUS_UPLOADED, TaxProof::STATUS_PARTIAL])
            ->count();

        $now = now();
        $monthlyTds = (float) TdsMonthlyCalculation::query()
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->when($fyId, fn ($q) => $q->where('tax_financial_year_id', $fyId))
            ->sum('tds_amount');

        $annualTaxLiability = (float) TaxProjection::query()
            ->when($fyId, fn ($q) => $q->where('tax_financial_year_id', $fyId))
            ->sum('annual_tax_liability');

        $eligibleStatuses = config('hrms.leave_applicable_employee_statuses', ['active', 'probation', 'notice_period']);
        $activeEmployeeIds = Employee::query()->whereIn('status', $eligibleStatuses)->pluck('id');

        $employeesWithRegime = EmployeeTaxRegime::query()
            ->where('status', 'active')
            ->when($fyId, fn ($q) => $q->where('tax_financial_year_id', $fyId))
            ->pluck('employee_id')
            ->unique();

        $employeesWithoutRegime = $activeEmployeeIds->diff($employeesWithRegime)->count();

        $verificationStatus = [
            'declarations' => [
                'draft' => $this->declarationCount(TaxDeclaration::STATUS_DRAFT, $fyId),
                'submitted' => $this->declarationCount(TaxDeclaration::STATUS_SUBMITTED, $fyId),
                'verified' => $this->declarationCount(TaxDeclaration::STATUS_VERIFIED, $fyId),
                'rejected' => $this->declarationCount(TaxDeclaration::STATUS_REJECTED, $fyId),
            ],
            'proofs' => [
                'uploaded' => $this->proofCount(TaxProof::STATUS_UPLOADED),
                'verified' => $this->proofCount(TaxProof::STATUS_VERIFIED),
                'partial' => $this->proofCount(TaxProof::STATUS_PARTIAL),
                'rejected' => $this->proofCount(TaxProof::STATUS_REJECTED),
            ],
        ];

        return [
            'financial_year' => $fy ? [
                'id' => $fy->id,
                'code' => $fy->code,
                'label' => $fy->label,
            ] : null,
            'pending_declarations' => $pendingDeclarations,
            'pending_proof_verification' => $pendingProofVerification,
            'monthly_tds' => round($monthlyTds, 2),
            'annual_tax_liability' => round($annualTaxLiability, 2),
            'employees_without_regime' => $employeesWithoutRegime,
            'verification_status' => $verificationStatus,
        ];
    }

    protected function declarationCount(string $status, ?int $fyId): int
    {
        return TaxDeclaration::query()
            ->where('status', $status)
            ->when($fyId, fn ($q) => $q->where('tax_financial_year_id', $fyId))
            ->count();
    }

    protected function proofCount(string $status): int
    {
        return TaxProof::query()->where('status', $status)->count();
    }
}
