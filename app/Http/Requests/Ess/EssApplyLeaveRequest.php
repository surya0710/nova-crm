<?php

namespace App\Http\Requests\Ess;

use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EssApplyLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = app(EssContext::class)->requireEmployee($this->user());

        return $this->user()?->can('applyLeave', $employee) ?? false;
    }

    public function rules(): array
    {
        $org = app(EssContext::class)->requireEmployee($this->user())->organization_id;

        return [
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('leave_types', 'id')->where('organization_id', $org),
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
        ];
    }
}
