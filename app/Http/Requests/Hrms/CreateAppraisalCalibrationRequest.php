<?php

namespace App\Http\Requests\Hrms;

use App\Models\AppraisalCalibration;
use Illuminate\Foundation\Http\FormRequest;

class CreateAppraisalCalibrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AppraisalCalibration::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'appraisal_session_id' => ['required', 'integer', 'exists:appraisal_sessions,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'participant_employee_ids' => ['nullable', 'array'],
            'participant_employee_ids.*' => ['integer', 'exists:employees,id'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
