<?php

namespace App\Http\Requests\Hrms;

use App\Models\EmployeeTaxRegime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectTaxRegimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('select', EmployeeTaxRegime::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($q) => $q->where('organization_id', session('current_organization_id'))),
            ],
            'regime' => ['required', Rule::in(['old', 'new'])],
            'effective_from' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
