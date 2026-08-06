<?php

namespace App\Http\Requests\Hrms;

use App\Models\SalaryAdvance;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSalaryAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SalaryAdvance::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'employee_id' => [
                'required', 'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'monthly_recovery' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
