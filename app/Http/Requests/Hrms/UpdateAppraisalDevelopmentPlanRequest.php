<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppraisalDevelopmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('appraisal'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'strengths' => ['nullable', 'string'],
            'improvement_areas' => ['nullable', 'string'],
            'learning_objectives' => ['nullable', 'string'],
            'required_training' => ['nullable', 'string'],
            'career_aspirations' => ['nullable', 'string'],
            'target_completion_date' => ['nullable', 'date'],
        ];
    }
}
