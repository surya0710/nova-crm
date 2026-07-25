<?php

namespace App\Http\Requests\Hrms;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', AttendanceRecord::class) ?? false;
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
            'clock_out_at' => ['nullable', 'date'],
        ];
    }

    public function employee(): Employee
    {
        return Employee::query()->findOrFail((int) $this->validated('employee_id'));
    }
}
