<?php

namespace App\Http\Requests;

use App\Models\CustomerTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionCustomerTicketRequest extends FormRequest
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

        return [
            'status' => [
                'required',
                'string',
                Rule::in($ticket instanceof CustomerTicket ? $ticket->allowedTransitions() : array_keys(config('customer_tickets.statuses'))),
            ],
        ];
    }
}
