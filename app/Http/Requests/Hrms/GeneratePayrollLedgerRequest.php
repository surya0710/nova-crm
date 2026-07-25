<?php

namespace App\Http\Requests\Hrms;

use App\Models\PayrollLedgerEntry;
use App\Models\PayrollRun;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePayrollLedgerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('generate', PayrollLedgerEntry::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'payroll_run_id' => [
                'required', 'integer',
                Rule::exists('payroll_runs', 'id')->where('organization_id', $org?->id),
            ],
        ];
    }

    public function payrollRun(): PayrollRun
    {
        return PayrollRun::query()->findOrFail($this->validated('payroll_run_id'));
    }
}
