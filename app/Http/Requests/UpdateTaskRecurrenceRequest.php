<?php

namespace App\Http\Requests;

use App\Models\TaskRecurrence;
use App\Services\TaskRecurrenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRecurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recurrence = $this->route('recurrence');

        return $recurrence instanceof TaskRecurrence
            && ($this->user()?->can('update', $recurrence) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recurrence_type' => ['sometimes', 'required', 'string', Rule::in(TaskRecurrenceService::TYPES)],
            'interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['nullable'],
            'end_type' => ['sometimes', 'required', 'string', Rule::in(TaskRecurrenceService::END_TYPES)],
            'end_date' => ['nullable', 'date'],
            'occurrences' => ['nullable', 'integer', 'min:1'],
            'skip_holidays' => ['nullable', 'boolean'],
            'copy_attachments' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
