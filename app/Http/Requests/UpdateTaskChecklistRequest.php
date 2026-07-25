<?php

namespace App\Http\Requests;

use App\Models\TaskChecklist;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checklist = $this->route('checklist');

        return $checklist instanceof TaskChecklist
            && ($this->user()?->can('update', $checklist) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'is_completed' => ['nullable', 'boolean'],
        ];
    }
}
