<?php

namespace App\Http\Requests;

use App\Models\CustomerTicket;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerTicketNoteRequest extends FormRequest
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
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
