<?php

namespace App\Http\Requests;

use App\Models\Contact;

class UpdateContactRequest extends StoreContactRequest
{
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact instanceof Contact
            && ($this->user()?->can('update', $contact) ?? false);
    }
}
