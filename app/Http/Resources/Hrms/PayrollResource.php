<?php

namespace App\Http\Resources\Hrms;

use App\Models\EmployeeSalaryAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmployeeSalaryAssignment */
class PayrollResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'annual_ctc' => $this->annual_ctc,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_until' => $this->effective_until?->toDateString(),
            'salary_structure' => $this->whenLoaded('salaryStructure', fn () => [
                'id' => $this->salaryStructure?->id,
                'name' => $this->salaryStructure?->name,
                'code' => $this->salaryStructure?->code,
            ]),
        ];
    }
}
