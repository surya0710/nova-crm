<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class CancelExitProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('exitProcess')) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'hr_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
