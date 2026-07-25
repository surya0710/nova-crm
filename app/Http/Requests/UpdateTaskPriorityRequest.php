<?php

namespace App\Http\Requests;

class UpdateTaskPriorityRequest extends StoreTaskPriorityRequest
{
    public function authorize(): bool
    {
        $priority = $this->route('priority');

        return $priority && ($this->user()?->can('update', $priority) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $organizationId = app(\App\Services\TenantContext::class)->get()?->id;
        $priority = $this->route('priority');

        $rules['slug'] = [
            'nullable',
            'string',
            'max:255',
            \Illuminate\Validation\Rule::unique('task_priorities', 'slug')
                ->where('organization_id', $organizationId)
                ->ignore($priority?->id),
        ];

        return $rules;
    }
}
