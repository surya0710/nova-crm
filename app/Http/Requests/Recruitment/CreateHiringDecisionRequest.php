<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class CreateHiringDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\HiringDecision::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'job_application_id' => ['required', 'integer', 'exists:job_applications,id'],
            'recommendation' => ['required', 'string', 'in:'.implode(',', array_keys(config('hrms.recruitment.hiring_recommendations', [])))],
            'decision_date' => ['nullable', 'date'],
            'final_notes' => ['nullable', 'string'],
        ];
    }
}
