<?php

namespace App\Http\Requests\Hrms;

use App\Models\EmployeeStatutoryProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEmployeeStatutoryProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = EmployeeStatutoryProfile::query()
            ->where('employee_id', $this->input('employee_id'))
            ->first();

        if ($profile) {
            return $this->user()?->can('update', $profile) ?? false;
        }

        return $this->user()?->can('create', EmployeeStatutoryProfile::class) ?? false;
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
            'pf_eligible' => ['sometimes', 'boolean'],
            'pf_uan' => ['nullable', 'string', 'max:20'],
            'esi_eligible' => ['sometimes', 'boolean'],
            'esi_number' => ['nullable', 'string', 'max:30'],
            'professional_tax_state' => ['nullable', 'string', 'max:10'],
            'tax_regime' => ['nullable', Rule::in(array_keys(config('hrms.statutory.tax_regimes', ['old' => 'Old', 'new' => 'New'])))],
            'pan' => ['nullable', 'string', 'max:20'],
            'aadhaar' => ['nullable', 'string', 'max:20'],
            'tan_reference' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pf_eligible' => $this->boolean('pf_eligible'),
            'esi_eligible' => $this->boolean('esi_eligible'),
        ]);
    }
}
