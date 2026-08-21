<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdjustmentNoteRequest extends FormRequest
{
    use ValidatesAdjustmentNoteItems;

    public function authorize(): bool
    {
        $note = $this->route('adjustment_note');

        return $note && ($this->user()?->can('update', $note) ?? false);
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
        return $this->adjustmentNoteFieldRules(true);
    }
}
