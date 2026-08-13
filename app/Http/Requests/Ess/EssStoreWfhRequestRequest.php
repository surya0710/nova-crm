<?php

namespace App\Http\Requests\Ess;

use Illuminate\Foundation\Http\FormRequest;

class EssStoreWfhRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('ess.access') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'work_date' => ['nullable', 'date', 'required_without:start_date'],
            'start_date' => ['nullable', 'date', 'required_without:work_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Single-day form posts work_date only; normalize to a range.
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
