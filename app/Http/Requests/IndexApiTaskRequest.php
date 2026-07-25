<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class IndexApiTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Task::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:50'],
            'status_id' => ['sometimes', 'integer'],
            'priority' => ['sometimes', 'string', 'max:50'],
            'priority_id' => ['sometimes', 'integer'],
            'assigned_to' => ['sometimes', 'integer'],
            'project_id' => ['sometimes', 'integer'],
            'is_archived' => ['sometimes', 'boolean'],
            'filter' => ['sometimes', 'string', 'in:overdue,due_today'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }
}
