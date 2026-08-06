<?php

namespace App\Http\Requests;

use App\Models\Task;

class StoreTaskRequest extends TaskRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Task::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->baseRules();
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (! $this->filled('status') && ! $this->filled('status_id')) {
            $this->merge(['status' => 'pending']);
        }

        if (! $this->filled('priority') && ! $this->filled('priority_id')) {
            $this->merge(['priority' => 'medium']);
        }
    }
}
