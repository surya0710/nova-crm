<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Validation\Rule;

class UpdateProjectLifecycleStageRequest extends StoreProjectLifecycleStageRequest
{
    public function authorize(): bool
    {
        $stage = $this->route('stage');

        return $stage && ($this->user()?->can('update', $stage) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;
        $stage = $this->route('stage');

        return [
            ...parent::rules(),
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('project_lifecycle_stages', 'slug')
                    ->where('organization_id', $organizationId)
                    ->ignore($stage?->id),
            ],
        ];
    }
}
