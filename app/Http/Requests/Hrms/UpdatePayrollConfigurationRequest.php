<?php

namespace App\Http\Requests\Hrms;

use App\Models\PayrollConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', PayrollConfiguration::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'payroll_frequency' => ['required', Rule::in(array_keys(config('hrms.payroll_frequencies', [])))],
            'currency' => ['required', 'string', 'max:10'],
            'working_days_per_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'week_off_days' => ['nullable', 'array'],
            'week_off_days.*' => ['string', Rule::in([
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            ])],
            'overtime_handling' => ['required', Rule::in(array_keys(config('hrms.payroll_overtime_handling', [])))],
            'rounding_policy' => ['required', Rule::in(array_keys(config('hrms.payroll_rounding_policies', [])))],
            'salary_mode' => ['required', Rule::in(array_keys(config('hrms.payroll.salary_modes', [])))],
            'salary_credit_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'auto_generate' => ['sometimes', 'boolean'],
            'reminder_days_before_credit' => ['nullable', 'integer', 'min:0', 'max:30'],
        ];
    }
}
