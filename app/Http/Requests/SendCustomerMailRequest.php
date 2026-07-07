<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClientEmailFields;
use Illuminate\Foundation\Http\FormRequest;

class SendCustomerMailRequest extends FormRequest
{
    use ValidatesClientEmailFields;

    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer && ($this->user()?->can('update', $customer) ?? false);
    }

    public function rules(): array
    {
        return $this->clientEmailRules(requireSubject: true);
    }
}
