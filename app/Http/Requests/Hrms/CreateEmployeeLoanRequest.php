<?php

namespace App\Http\Requests\Hrms;

use App\Models\EmployeeLoan;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeLoan::class) ?? false;
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
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'monthly_recovery' => ['required', 'numeric', 'min:0.01'],
            'loan_type' => ['nullable', 'string', 'max:50'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'disbursed_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
