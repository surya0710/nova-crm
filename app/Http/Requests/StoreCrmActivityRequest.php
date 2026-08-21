<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact instanceof Contact
            && ($this->user()?->can('update', $contact) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->string('type')->toString();

        return [
            'type' => ['required', 'string', Rule::in(array_keys(config('crm_activities.types') ?? []))],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'occurred_at' => ['nullable', 'date'],
            'due_at' => [$type === 'follow_up' ? 'required' : 'nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'direction' => ['nullable', 'string', Rule::in(array_keys(config('crm_activities.directions') ?? []))],
            'outcome' => ['nullable', 'string', Rule::in(array_keys(config('crm_activities.outcomes') ?? []))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['subject', 'body', 'direction', 'outcome'] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }

        if (! $this->filled('subject') && $this->filled('type')) {
            $this->merge([
                'subject' => config('crm_activities.types.'.$this->string('type')->toString(), __('Activity')),
            ]);
        }
    }
}
