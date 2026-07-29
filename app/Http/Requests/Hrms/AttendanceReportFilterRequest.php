<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('attendance.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['nullable', 'string', Rule::in(array_keys(config('hrms.attendance_reports.types', [])))],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'department_id' => ['nullable', 'integer', 'exists:hrms_departments,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'format' => ['nullable', 'string', Rule::in(array_keys(config('hrms.attendance_reports.export_formats', [])))],
        ];
    }

    /**
     * @return array{year: int, month: int, department_id: int|null, employee_id: int|null}
     */
    public function filters(): array
    {
        return [
            'year' => (int) $this->input('year', now()->year),
            'month' => (int) $this->input('month', now()->month),
            'department_id' => $this->filled('department_id') ? (int) $this->input('department_id') : null,
            'employee_id' => $this->filled('employee_id') ? (int) $this->input('employee_id') : null,
        ];
    }
}
