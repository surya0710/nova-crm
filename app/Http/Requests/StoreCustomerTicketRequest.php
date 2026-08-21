<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            && ($this->user()?->can('create', [\App\Models\CustomerTicket::class, $customer]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'string', Rule::in(array_keys(config('customer_tickets.statuses')))],
            'priority' => ['required', 'string', Rule::in(array_keys(config('customer_tickets.priorities')))],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('customer_id', $customer?->id),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $customer?->organization_id),
            ],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
