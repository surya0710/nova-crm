<?php

namespace App\Http\Requests;

use App\Models\ProjectTemplate;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProjectTemplate::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('project_templates', 'slug')->where('organization_id', $organizationId),
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
