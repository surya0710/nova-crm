<?php

namespace App\Http\Requests;

use App\Models\Workflow;
use App\Services\TenantContext;

class UpdateWorkflowRequest extends StoreWorkflowRequest
{
    public function authorize(): bool
    {
        $workflow = $this->route('workflow');
        $organization = $workflow instanceof Workflow
            ? $workflow->organization
            : app(TenantContext::class)->get();

        return $organization && ($this->user()?->hasPermission('workflows.update', $organization) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();

        foreach (['name', 'description', 'trigger_type', 'trigger_config', 'conditions', 'actions'] as $field) {
            $rules[$field] = array_values(array_filter(
                $rules[$field],
                fn (mixed $rule) => $rule !== 'required' && $rule !== 'present',
            ));
            array_unshift($rules[$field], 'sometimes');
        }

        return $rules;
    }
}
