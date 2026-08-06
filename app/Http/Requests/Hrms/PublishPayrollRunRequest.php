<?php

namespace App\Http\Requests\Hrms;

use App\Models\PayrollRun;
use Illuminate\Foundation\Http\FormRequest;

class PublishPayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PayrollRun $run */
        $run = $this->route('run');

        return $this->user()?->can('publish', $run) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'send_emails' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('send_emails')) {
            $this->merge(['send_emails' => $this->boolean('send_emails')]);
        }
    }
}
