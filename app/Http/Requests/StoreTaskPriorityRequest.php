<?php

namespace App\Http\Requests;

use App\Models\TaskPriority;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TaskPriority::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('task_priorities', 'slug')->where('organization_id', $organizationId),
            ],
            'color' => ['nullable', 'string', 'max:20'],
            'level' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
