<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('manageCalendar', $project) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $providers = array_keys(config('projects.calendar_providers', [
            'internal' => 'Internal',
            'google' => 'Google',
            'outlook' => 'Outlook',
        ]));

        return [
            'provider' => ['nullable', 'string', Rule::in($providers)],
        ];
    }
}
