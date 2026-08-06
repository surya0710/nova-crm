<?php

namespace App\Http\Requests\Hrms;

use App\Models\PayrollRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PayrollRun $run */
        $run = $this->route('run');

        return $this->user()?->can('approve', $run) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'approval_type' => ['nullable', Rule::in(array_keys(config('hrms.payroll.approval_types', ['hr' => 'HR'])))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
