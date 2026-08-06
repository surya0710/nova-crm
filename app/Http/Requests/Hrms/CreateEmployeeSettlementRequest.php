<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use App\Models\EmployeeSettlement;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeSettlement::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'employee_id' => [
                'required', 'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id),
            ],
            'pending_salary' => ['nullable', 'numeric', 'min:0'],
            'leave_encashment' => ['nullable', 'numeric', 'min:0'],
            'asset_deductions' => ['nullable', 'numeric', 'min:0'],
            'statutory_deductions' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function employee(): Employee
    {
        return Employee::query()->findOrFail($this->validated('employee_id'));
    }
}
