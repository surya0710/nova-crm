<?php

namespace App\Http\Requests;

use App\Models\CustomerTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof CustomerTicket
            && ($this->user()?->can('update', $ticket) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ticket = $this->route('ticket');
        $customer = $ticket instanceof CustomerTicket ? $ticket->customer : null;

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
