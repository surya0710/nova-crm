<?php

namespace App\Http\Requests\Hrms;

use App\Models\LeaveApplication;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustLeaveBalanceRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'remarks' => ['required', 'string', 'max:2000'],
        ];
    }
}
