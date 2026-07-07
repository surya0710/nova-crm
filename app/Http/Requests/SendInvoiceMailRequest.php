<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClientEmailFields;
use Illuminate\Foundation\Http\FormRequest;

class SendInvoiceMailRequest extends FormRequest
{
    use ValidatesClientEmailFields;

    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice && ($this->user()?->can('send', $invoice) ?? false);
    }

    public function rules(): array
    {
        return $this->clientEmailRules();
    }
}
