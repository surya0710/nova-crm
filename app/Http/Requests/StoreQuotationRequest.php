<?php

namespace App\Http\Requests;

use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    use ValidatesQuotationItems;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Quotation::class) ?? false;
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
        return $this->quotationFieldRules();
    }
}
