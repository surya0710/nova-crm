<?php

namespace App\Http\Requests;

class UpdateTaskRequest extends TaskRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task && ($this->user()?->can('update', $task) ?? false);
    }

    public function rules(): array
    {
        return $this->baseRules();
    }
}
