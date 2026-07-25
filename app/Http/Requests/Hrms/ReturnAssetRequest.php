<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class ReturnAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('return', $this->route('asset')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'return_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
