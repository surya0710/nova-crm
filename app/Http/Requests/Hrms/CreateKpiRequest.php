<?php

namespace App\Http\Requests\Hrms;

use App\Models\Kpi;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateKpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Kpi::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('kpis', 'code')->where('organization_id', $org?->id),
            ],
            'unit' => ['nullable', 'string', 'max:50'],
            'measurement_type' => ['required', 'string', Rule::in(array_keys(config('hrms.goal_measurement_types', [])))],
            'default_target' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }
}
