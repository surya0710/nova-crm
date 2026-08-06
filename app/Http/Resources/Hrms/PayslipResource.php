<?php

namespace App\Http\Resources\Hrms;

use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payslip */
class PayslipResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $snapshot = is_array($this->snapshot) ? $this->snapshot : [];

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'payslip_number' => $this->payslip_number,
            'gross_salary' => $this->gross_salary,
            'total_earnings' => $this->total_earnings,
            'total_deductions' => $this->total_deductions,
            'employer_contributions' => $this->employer_contributions,
            'net_salary' => $this->net_salary,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'has_pdf' => $this->hasPdf(),
            'earnings' => $snapshot['earnings'] ?? [],
            'deductions' => collect($snapshot['deductions'] ?? [])
                ->reject(fn ($line) => ($line['component_type'] ?? '') === 'employer_contribution')
                ->values()
                ->all(),
            'employer_contribution_lines' => collect($snapshot['deductions'] ?? [])
                ->filter(fn ($line) => ($line['component_type'] ?? '') === 'employer_contribution')
                ->values()
                ->all(),
            'statutory' => $snapshot['statutory'] ?? null,
            'period' => $this->when(
                $this->relationLoaded('payrollRun'),
                fn () => [
                    'id' => $this->payrollRun?->period?->id,
                    'name' => $this->payrollRun?->period?->name,
                    'start_date' => $this->payrollRun?->period?->start_date?->toDateString(),
                    'end_date' => $this->payrollRun?->period?->end_date?->toDateString(),
                ]
            ),
        ];
    }
}
