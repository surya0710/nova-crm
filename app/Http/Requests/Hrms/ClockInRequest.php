<?php

namespace App\Http\Requests\Hrms;

use App\Http\Requests\Concerns\CapturesAttendanceVerificationContext;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClockInRequest extends FormRequest
{
    use CapturesAttendanceVerificationContext;

    public function authorize(): bool
    {
        return $this->user()?->can('manage', AttendanceRecord::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return array_merge([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id),
            ],
            'clock_in_at' => ['nullable', 'date'],
        ], $this->verificationRules());
    }

    public function employee(): Employee
    {
        return Employee::query()->findOrFail((int) $this->validated('employee_id'));
    }
}
