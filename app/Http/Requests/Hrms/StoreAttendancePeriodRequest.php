<?php

namespace App\Http\Requests\Hrms;

use App\Models\AttendancePeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendancePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'payroll_period_id' => [
                'nullable',
                'integer',
                Rule::exists('payroll_periods', 'id'),
            ],
        ];
    }

    /** @return array{name: string, start_date: string, end_date: string, payroll_period_id?: int|null} */
    public function periodData(): array
    {
        return [
            'name' => (string) $this->input('name'),
            'start_date' => (string) $this->input('start_date'),
            'end_date' => (string) $this->input('end_date'),
            'payroll_period_id' => $this->filled('payroll_period_id')
                ? (int) $this->input('payroll_period_id')
                : null,
        ];
    }
}
