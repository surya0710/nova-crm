<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClientEmailFields;
use Illuminate\Foundation\Http\FormRequest;

class SendContactMailRequest extends FormRequest
{
    use ValidatesClientEmailFields;

    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact && ($this->user()?->can('update', $contact) ?? false);
    }

    public function rules(): array
    {
        return $this->clientEmailRules(requireSubject: true);
    }
}
