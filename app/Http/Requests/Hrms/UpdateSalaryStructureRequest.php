<?php

namespace App\Http\Requests\Hrms;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('structure')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        $structure = $this->route('structure');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('salary_structures', 'name')
                    ->where('organization_id', $org?->id)
                    ->ignore($structure?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'effective_date' => ['required', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'components' => ['nullable', 'array'],
            'components.*.salary_component_id' => [
                'required', 'integer',
                Rule::exists('salary_components', 'id')->where('organization_id', $org?->id),
            ],
            'components.*.calculation_type' => ['required', Rule::in(array_keys(config('hrms.salary_calculation_types', [])))],
            'components.*.amount' => ['nullable', 'numeric', 'min:0'],
            'components.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'components.*.based_on_component_id' => [
                'nullable', 'integer',
                Rule::exists('salary_components', 'id')->where('organization_id', $org?->id),
            ],
            'components.*.formula' => ['nullable', 'string', 'max:2000'],
            'components.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [
            'is_active' => $this->boolean('is_active'),
        ];

        if ($this->has('components')) {
            $payload['components'] = collect($this->input('components', []))
                ->filter(fn ($row) => filled($row['salary_component_id'] ?? null))
                ->values()
                ->all();
        }

        $this->merge($payload);
    }
}
