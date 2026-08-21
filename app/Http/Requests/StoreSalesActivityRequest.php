<?php

namespace App\Http\Requests;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $opportunity = $this->route('opportunity');
        if ($opportunity instanceof Opportunity) {
            return $user->can('update', $opportunity) || $user->can('create', \App\Models\CrmActivity::class);
        }

        $customer = $this->route('customer');
        if ($customer instanceof Customer) {
            return $user->can('update', $customer);
        }

        $contact = $this->route('contact');
        if ($contact instanceof Contact) {
            return $user->can('update', $contact);
        }

        return $user->can('create', \App\Models\CrmActivity::class);
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
            'due_at' => [in_array($type, ['follow_up', 'task'], true) ? 'required' : 'nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'direction' => ['nullable', 'string', Rule::in(array_keys(config('crm_activities.directions') ?? []))],
            'outcome' => ['nullable', 'string', Rule::in(array_keys(config('crm_activities.outcomes') ?? []))],
            'status' => ['nullable', 'string', Rule::in(array_keys(config('crm_activities.statuses') ?? []))],
            'priority' => ['nullable', 'string', Rule::in(array_keys(config('crm_activities.priorities') ?? []))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['subject', 'body', 'direction', 'outcome', 'status', 'priority'] as $field) {
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
