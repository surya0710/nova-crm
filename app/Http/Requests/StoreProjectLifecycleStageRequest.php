<?php

namespace App\Http\Requests;

use App\Models\ProjectLifecycleStage;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectLifecycleStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProjectLifecycleStage::class) ?? false;
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
                Rule::unique('project_lifecycle_stages', 'slug')->where('organization_id', $organizationId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
