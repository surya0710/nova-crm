<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', Rule::in(array_keys(config('export.entities', [])))],
            'format' => ['required', 'string', Rule::in(array_keys(config('export.formats', [])))],
            'selection_mode' => ['required', 'in:ids,page,selected,all,filtered,complete'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'filters' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
            'redirect_to' => ['nullable', 'string'],
        ];
    }
}
