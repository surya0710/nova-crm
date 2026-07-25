<?php

namespace App\Http\Requests\Hrms;

use App\Models\PerformanceRatingScale;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerformanceRatingScaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PerformanceRatingScale $scale */
        $scale = $this->route('rating_scale');

        return $this->user()?->can('update', $scale) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        /** @var PerformanceRatingScale $scale */
        $scale = $this->route('rating_scale');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('performance_rating_scales', 'code')
                    ->where('organization_id', $org?->id)
                    ->ignore($scale->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'levels' => ['sometimes', 'array', 'min:1'],
            'levels.*.value' => ['required_with:levels', 'integer', 'min:1', 'max:100'],
            'levels.*.label' => ['required_with:levels', 'string', 'max:255'],
            'levels.*.description' => ['nullable', 'string', 'max:2000'],
            'levels.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
