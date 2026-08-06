<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('projects.notifications.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();
        $frequencies = array_keys(config('projects.notification_digest_frequencies', [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
        ]));

        return [
            'in_app_enabled' => ['sometimes', 'boolean'],
            'email_enabled' => ['sometimes', 'boolean'],
            'digest_enabled' => ['sometimes', 'boolean'],
            'digest_frequency' => ['nullable', 'string', Rule::in($frequencies)],
            'muted_projects' => ['nullable', 'array'],
            'muted_projects.*' => [
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'muted_tasks' => ['nullable', 'array'],
            'muted_tasks.*' => [
                'integer',
                Rule::exists('tasks', 'id')->where('organization_id', $organizationId),
            ],
            'event_preferences' => ['nullable', 'array'],
            'channels' => ['nullable', 'array'],
        ];
    }
}
