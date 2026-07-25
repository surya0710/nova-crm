<?php

namespace App\Http\Requests;

use App\Models\ProjectTemplate;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectFromTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('template') ?? $this->route('project_template');

        return ($this->user()?->can('create', \App\Models\Project::class) ?? false)
            && $template instanceof ProjectTemplate
            && ($this->user()?->can('view', $template) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'objective' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'owner_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('organization_id', $organizationId),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('hrms_departments', 'id')->where('organization_id', $organizationId),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('project_categories', 'id')->where('organization_id', $organizationId),
            ],
            'project_type_id' => [
                'nullable',
                'integer',
                Rule::exists('project_types', 'id')->where('organization_id', $organizationId),
            ],
            'priority' => ['nullable', 'string', 'max:50'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
