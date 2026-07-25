<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class SubmitEmployeeAppraisalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submit', $this->route('appraisal'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'final_comments' => ['nullable', 'string'],
            'overall_summary' => ['nullable', 'string'],
            'manager_recommendation' => ['nullable', 'string'],
        ];
    }
}
