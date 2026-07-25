<?php

namespace App\Http\Requests;

use App\Models\ProjectTemplate;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('template') ?? $this->route('project_template');

        return $template instanceof ProjectTemplate
            && ($this->user()?->can('update', $template) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $template = $this->route('template') ?? $this->route('project_template');
        $organizationId = $template instanceof ProjectTemplate
            ? $template->organization_id
            : app(TenantContext::class)->id();
        $templateId = $template instanceof ProjectTemplate ? $template->id : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('project_templates', 'slug')
                    ->where('organization_id', $organizationId)
                    ->ignore($templateId),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('hrms_departments', 'id')->where('organization_id', $organizationId),
            ],
            'defaults' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'is_favorite' => ['nullable', 'boolean'],
        ];
    }
}
