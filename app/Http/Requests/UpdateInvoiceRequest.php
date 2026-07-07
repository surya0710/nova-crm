<?php

namespace App\Http\Requests;

class UpdateInvoiceRequest extends StoreInvoiceRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice && ($this->user()?->can('update', $invoice) ?? false);
    }

    public function rules(): array
    {
        return $this->invoiceFieldRules(isUpdate: true);
    }
}
