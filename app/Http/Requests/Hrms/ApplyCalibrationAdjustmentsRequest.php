<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCalibrationAdjustmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('calibration'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'adjustments' => ['required', 'array', 'min:1'],
            'adjustments.*.employee_appraisal_id' => ['required', 'integer', 'exists:employee_appraisals,id'],
            'adjustments.*.proposed_rating' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'adjustments.*.final_rating' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'adjustments.*.comments' => ['nullable', 'string'],
        ];
    }
}
