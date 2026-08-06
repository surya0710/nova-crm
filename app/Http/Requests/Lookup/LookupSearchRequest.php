<?php

namespace App\Http\Requests\Lookup;

use Illuminate\Foundation\Http\FormRequest;

class LookupSearchRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('lookups.max_per_page', 50)],
            'id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
