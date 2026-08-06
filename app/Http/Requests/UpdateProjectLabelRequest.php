<?php

namespace App\Http\Requests;

use App\Models\ProjectLabel;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $label = $this->route('label') ?? $this->route('project_label');

        return $label instanceof ProjectLabel
            && ($this->user()?->can('update', $label) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
