<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Validation\Rule;

class UpdateProjectTypeRequest extends StoreProjectTypeRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');

        return $type && ($this->user()?->can('update', $type) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;
        $type = $this->route('type');

        return [
            ...parent::rules(),
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('project_types', 'slug')
                    ->where('organization_id', $organizationId)
                    ->ignore($type?->id),
            ],
        ];
    }
}
