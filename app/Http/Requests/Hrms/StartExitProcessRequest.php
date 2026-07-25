<?php

namespace App\Http\Requests\Hrms;

use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartExitProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeExitProcess::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'exit_type' => ['required', Rule::in(array_keys(config('hrms.exit_types', [])))],
            'last_working_day' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'exit_interview' => ['nullable', 'string', 'max:5000'],
            'hr_notes' => ['nullable', 'string', 'max:2000'],
            'manager_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function employee(): Employee
    {
        return Employee::query()->findOrFail($this->validated('employee_id'));
    }
}
