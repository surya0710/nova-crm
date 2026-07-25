<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class SaveProjectAsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return ($this->user()?->can('create', \App\Models\ProjectTemplate::class) ?? false)
            && $project instanceof Project
            && ($this->user()?->can('view', $project) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer'],
            'defaults' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
