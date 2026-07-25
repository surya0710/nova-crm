<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class IndexApiProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Project::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'status_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'integer'],
            'owner_id' => ['sometimes', 'integer'],
            'manager_id' => ['sometimes', 'integer'],
            'priority' => ['sometimes', 'string', 'max:50'],
            'is_archived' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }
}
