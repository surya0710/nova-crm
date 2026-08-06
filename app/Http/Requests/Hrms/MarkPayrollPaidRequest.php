<?php

namespace App\Http\Requests\Hrms;

use App\Models\PayrollRun;
use Illuminate\Foundation\Http\FormRequest;

class MarkPayrollPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PayrollRun|null $run */
        $run = $this->route('run');

        if (! $run instanceof PayrollRun) {
            return false;
        }

        return $this->user()?->can('pay', $run) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'payment_reference' => ['required', 'string', 'max:120'],
            'payment_date' => ['nullable', 'date'],
            'payment_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
