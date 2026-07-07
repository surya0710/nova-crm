<?php

namespace App\Http\Requests;

class StoreTaskRequest extends TaskRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Task::class) ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => 'pending']);
        }

        if (! $this->filled('priority')) {
            $this->merge(['priority' => 'medium']);
        }
    }
}
