<?php

namespace App\Http\Requests\Hrms;

use App\Models\LeaveApplication;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LeaveApplication::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id),
            ],
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('leave_types', 'id')->where('organization_id', $org?->id),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['sometimes', 'boolean'],
            'half_day_period' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('hrms.half_day_periods', []))),
            ],
            'reason' => ['nullable', 'string', 'max:2000'],
            'submit' => ['sometimes', 'boolean'],
        ];
    }
}
