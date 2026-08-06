<?php

namespace App\Http\Requests\Hrms;

use App\Models\PerformanceRatingScale;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePerformanceRatingScaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PerformanceRatingScale::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('performance_rating_scales', 'code')->where('organization_id', $org?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.value' => ['required', 'integer', 'min:1', 'max:100'],
            'levels.*.label' => ['required', 'string', 'max:255'],
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
