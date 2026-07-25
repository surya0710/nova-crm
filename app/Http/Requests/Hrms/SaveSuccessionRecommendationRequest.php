<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSuccessionRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('appraisal'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'critical_role_flag' => ['nullable', 'boolean'],
            'readiness_level' => ['nullable', 'string', Rule::in(array_keys(config('hrms.succession_readiness_levels', [])))],
            'succession_notes' => ['nullable', 'string'],
        ];
    }
}
