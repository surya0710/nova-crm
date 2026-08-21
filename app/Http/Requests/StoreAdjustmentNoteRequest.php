<?php

namespace App\Http\Requests;

use App\Models\AdjustmentNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdjustmentNoteRequest extends FormRequest
{
    use ValidatesAdjustmentNoteItems;

    public function authorize(): bool
    {
        return $this->user()?->can('create', AdjustmentNote::class) ?? false;
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
        return array_merge($this->adjustmentNoteFieldRules(), [
            'type' => ['nullable', 'string', Rule::in(['credit', 'debit'])],
        ]);
    }
}
