<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class ClassifyTalentMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('performance.talent.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'employee_appraisal_id' => ['required', 'integer', 'exists:employee_appraisals,id'],
            'performance_band' => ['required', 'integer', 'min:1', 'max:3'],
            'potential_band' => ['required', 'integer', 'min:1', 'max:3'],
            'performance_score' => ['nullable', 'numeric'],
            'potential_score' => ['nullable', 'numeric'],
            'classification' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
