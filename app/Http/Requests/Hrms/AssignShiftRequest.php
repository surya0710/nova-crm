<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use App\Models\HrmsShift;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HrmsShift::class) ?? false;
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
            'shift_id' => [
                'required',
                'integer',
                Rule::exists('hrms_shifts', 'id')->where('organization_id', $org?->id),
            ],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }

    public function employee(): Employee
    {
        return Employee::query()->findOrFail((int) $this->validated('employee_id'));
    }
}
