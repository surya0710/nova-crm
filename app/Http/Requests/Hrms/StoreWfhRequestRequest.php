<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class StoreWfhRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\WfhRequest::class)
            || $this->user()?->hasPermission('wfh.manage')
            || $this->user()?->hasPermission('ess.access');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'work_date' => ['nullable', 'date', 'required_without:start_date'],
            'start_date' => ['nullable', 'date', 'required_without:work_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'submit' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('work_date') && ! $this->filled('start_date')) {
            $this->merge([
                'start_date' => $this->input('work_date'),
                'end_date' => $this->input('end_date') ?: $this->input('work_date'),
            ]);
        }

        if ($this->filled('start_date') && ! $this->filled('end_date')) {
            $this->merge(['end_date' => $this->input('start_date')]);
        }

        if ($this->filled('start_date') && ! $this->filled('work_date')) {
            $this->merge(['work_date' => $this->input('start_date')]);
        }
    }
}
