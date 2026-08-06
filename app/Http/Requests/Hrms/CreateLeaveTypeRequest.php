<?php

namespace App\Http\Requests\Hrms;

use App\Models\LeaveType;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LeaveType::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('leave_types', 'code')->where('organization_id', $org?->id)],
            'is_paid' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'requires_hr_approval' => ['sometimes', 'boolean'],
            'allow_half_day' => ['sometimes', 'boolean'],
            'max_days_per_year' => ['nullable', 'integer', 'min:0', 'max:365'],
            'allocation_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'carry_forward_allowed' => ['sometimes', 'boolean'],
            'negative_balance_allowed' => ['sometimes', 'boolean'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
