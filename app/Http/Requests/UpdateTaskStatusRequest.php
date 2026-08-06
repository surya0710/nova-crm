<?php

namespace App\Http\Requests;

class UpdateTaskStatusRequest extends StoreTaskStatusRequest
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
        $rules = parent::rules();
        $organizationId = app(\App\Services\TenantContext::class)->get()?->id;
        $status = $this->route('status');

        $rules['slug'] = [
            'nullable',
            'string',
            'max:255',
            \Illuminate\Validation\Rule::unique('task_statuses', 'slug')
                ->where('organization_id', $organizationId)
                ->ignore($status?->id),
        ];

        return $rules;
    }
}
