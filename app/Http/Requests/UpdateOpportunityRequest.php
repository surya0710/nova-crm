<?php

namespace App\Http\Requests;

class UpdateOpportunityRequest extends StoreOpportunityRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $opportunity && ($this->user()?->can('update', $opportunity) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $opportunity = $this->route('opportunity');

        if ($opportunity?->isClosed()) {
            unset($rules['stage']);
        }

        return $rules;
    }
}
