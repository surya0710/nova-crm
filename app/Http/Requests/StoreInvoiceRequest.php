<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    use ValidatesInvoiceItems;

    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Invoice::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);

        foreach ($items as $key => $item) {
            if (empty($item['product_id'])) {
                $items[$key]['product_id'] = null;
            }
        }

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return $this->invoiceFieldRules();
    }
}
