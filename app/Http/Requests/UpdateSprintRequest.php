<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'goal' => ['nullable', 'string', 'max:2000'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', Rule::in(array_keys(config('tasks.sprint_statuses', [])))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
