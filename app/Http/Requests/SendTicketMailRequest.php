<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClientEmailFields;
use Illuminate\Foundation\Http\FormRequest;

class SendTicketMailRequest extends FormRequest
{
    use ValidatesClientEmailFields;

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket && ($this->user()?->can('update', $ticket) ?? false);
    }

    public function rules(): array
    {
        return $this->clientEmailRules(requireSubject: true);
    }
}
