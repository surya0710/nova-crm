<?php

namespace App\Http\Requests\Hrms;

use App\Models\TaxFinancialYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxFinancialYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TaxFinancialYear::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30'],
            'label' => ['required', 'string', 'max:255'],
            'assessment_year' => ['required', 'string', 'max:30'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'default_regime' => ['required', Rule::in(['old', 'new'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
