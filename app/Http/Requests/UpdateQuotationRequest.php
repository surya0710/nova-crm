<?php

namespace App\Http\Requests;

class UpdateQuotationRequest extends StoreQuotationRequest
{
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');

        return $quotation && ($this->user()?->can('update', $quotation) ?? false);
    }
}
