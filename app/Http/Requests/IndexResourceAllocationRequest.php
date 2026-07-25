<?php

namespace App\Http\Requests;

use App\Models\ResourceAllocation;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexResourceAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ResourceAllocation::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();

        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'employee_id' => [
                'sometimes',
                'integer',
                Rule::exists('employees', 'id')->where('organization_id', $organizationId),
            ],
            'project_id' => [
                'sometimes',
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'task_id' => [
                'sometimes',
                'integer',
                Rule::exists('tasks', 'id')->where('organization_id', $organizationId),
            ],
            'allocation_type' => ['sometimes', 'string', Rule::in(array_keys(config('resources.allocation_types', [])))],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }
}
