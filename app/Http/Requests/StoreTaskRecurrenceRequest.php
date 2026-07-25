<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Services\TaskRecurrenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRecurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TaskRecurrence::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recurrence_type' => ['required', 'string', Rule::in(TaskRecurrenceService::TYPES)],
            'interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['nullable'],
            'end_type' => ['required', 'string', Rule::in(TaskRecurrenceService::END_TYPES)],
            'end_date' => ['nullable', 'date', 'required_if:end_type,date'],
            'occurrences' => ['nullable', 'integer', 'min:1', 'required_if:end_type,occurrences'],
            'skip_holidays' => ['nullable', 'boolean'],
            'copy_attachments' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
