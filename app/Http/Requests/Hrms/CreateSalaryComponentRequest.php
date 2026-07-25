<?php

namespace App\Http\Requests\Hrms;

use App\Models\SalaryComponent;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SalaryComponent::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('salary_components', 'code')->where('organization_id', $org?->id),
            ],
            'component_type' => ['required', Rule::in(array_keys(config('hrms.salary_component_types', [])))],
            'is_taxable' => ['sometimes', 'boolean'],
            'is_recurring' => ['sometimes', 'boolean'],
            'formula_supported' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_taxable' => $this->boolean('is_taxable'),
            'is_recurring' => $this->boolean('is_recurring', true),
            'formula_supported' => $this->boolean('formula_supported'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
