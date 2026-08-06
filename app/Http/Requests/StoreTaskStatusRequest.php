<?php

namespace App\Http\Requests;

use App\Models\TaskStatus;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TaskStatus::class) ?? false;
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
                Rule::unique('task_statuses', 'slug')->where('organization_id', $organizationId),
            ],
            'color' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
            'is_closed' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'wip_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
