<?php

namespace App\Http\Requests;

class UpdateLeadRequest extends StoreLeadRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead && ($this->user()?->can('update', $lead) ?? false);
    }
}
