<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Validation\Rule;

class UpdateProjectStatusRequest extends StoreProjectStatusRequest
{
    public function authorize(): bool
    {
        $status = $this->route('status');

        return $status && ($this->user()?->can('update', $status) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $organizationId = $organization?->id;
        $status = $this->route('status');

        return [
            ...parent::rules(),
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('project_statuses', 'slug')
                    ->where('organization_id', $organizationId)
                    ->ignore($status?->id),
            ],
        ];
    }
}
