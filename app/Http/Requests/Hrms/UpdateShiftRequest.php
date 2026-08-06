<?php

namespace App\Http\Requests\Hrms;

use App\Models\HrmsShift;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shift = $this->route('shift');

        return $shift instanceof HrmsShift && ($this->user()?->can('update', $shift) ?? false);
    }

    public function rules(): array
    {
        /** @var HrmsShift $shift */
        $shift = $this->route('shift');
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('hrms_shifts', 'code')->where('organization_id', $org?->id)->ignore($shift->id)],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i'],
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
