<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Validation\Rule;

class UpdateProjectCategoryRequest extends StoreProjectCategoryRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category && ($this->user()?->can('update', $category) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;
        $category = $this->route('category');

        return [
            ...parent::rules(),
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('project_categories', 'slug')
                    ->where('organization_id', $organizationId)
                    ->ignore($category?->id),
            ],
        ];
    }
}
