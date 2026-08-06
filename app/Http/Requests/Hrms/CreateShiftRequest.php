<?php

namespace App\Http\Requests\Hrms;

use App\Models\HrmsShift;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HrmsShift::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('hrms_shifts', 'code')->where('organization_id', $org?->id)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'grace_period_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'working_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'minimum_working_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'overtime_threshold_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'is_overnight' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
