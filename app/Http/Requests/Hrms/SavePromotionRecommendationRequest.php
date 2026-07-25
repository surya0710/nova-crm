<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePromotionRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('appraisal'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'promotion_recommendation' => ['required', 'string', Rule::in(array_keys(config('hrms.promotion_recommendation_levels', [])))],
            'target_designation_id' => ['nullable', 'integer', 'exists:hrms_designations,id'],
            'effective_date' => ['nullable', 'date'],
            'justification' => ['nullable', 'string'],
        ];
    }
}
