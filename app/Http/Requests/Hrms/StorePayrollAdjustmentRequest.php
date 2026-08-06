<?php

namespace App\Http\Requests\Hrms;

use App\Models\PayrollAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PayrollAdjustment::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'payroll_period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
            'adjustment_type' => ['required', Rule::in(array_keys(config('hrms.payroll.adjustment_types', [])))],
            'direction' => ['nullable', Rule::in(['earning', 'deduction'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'date'],
        ];
    }
}
