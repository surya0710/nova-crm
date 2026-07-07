<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClientEmailFields;
use Illuminate\Foundation\Http\FormRequest;

class SendQuotationMailRequest extends FormRequest
{
    use ValidatesClientEmailFields;

    public function authorize(): bool
    {
        $quotation = $this->route('quotation');

        return $quotation && ($this->user()?->can('update', $quotation) ?? false);
    }

    public function rules(): array
    {
        return $this->clientEmailRules();
    }
}
