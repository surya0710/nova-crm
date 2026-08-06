<?php

namespace App\Http\Requests;

use App\Models\ProjectLabel;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProjectLabel::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
