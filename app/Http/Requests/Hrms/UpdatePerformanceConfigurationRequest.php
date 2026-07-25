<?php

namespace App\Http\Requests\Hrms;

use App\Models\PerformanceConfiguration;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerformanceConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', PerformanceConfiguration::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'default_review_frequency' => ['required', Rule::in(array_keys(config('hrms.performance_review_frequencies', [])))],
            'rating_scale_id' => [
                'nullable', 'integer',
                Rule::exists('performance_rating_scales', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'goal_weighting' => ['required', 'numeric', 'min:0', 'max:100'],
            'competency_weighting' => ['required', 'numeric', 'min:0', 'max:100'],
            'review_visibility' => ['required', Rule::in(array_keys(config('hrms.performance_review_visibilities', [])))],
            'calibration_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'calibration_enabled' => $this->boolean('calibration_enabled'),
        ]);
    }
}
