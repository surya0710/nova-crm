<?php

namespace App\Http\Requests;

use App\Models\ProjectType;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProjectType::class) ?? false;
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
                Rule::unique('project_types', 'slug')->where('organization_id', $organizationId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'default_duration' => ['nullable', 'integer', 'min:1'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
