<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExitProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('exitProcess')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'exit_type' => ['sometimes', Rule::in(array_keys(config('hrms.exit_types', [])))],
            'last_working_day' => ['sometimes', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'exit_interview' => ['nullable', 'string', 'max:5000'],
            'hr_notes' => ['nullable', 'string', 'max:2000'],
            'manager_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(array_keys(config('hrms.exit_process_statuses', [])))],
            'checklist_assets_returned' => ['sometimes', 'boolean'],
            'checklist_documents_completed' => ['sometimes', 'boolean'],
            'checklist_knowledge_transfer' => ['sometimes', 'boolean'],
            'checklist_manager_approval' => ['sometimes', 'boolean'],
            'checklist_hr_approval' => ['sometimes', 'boolean'],
        ];
    }
}
