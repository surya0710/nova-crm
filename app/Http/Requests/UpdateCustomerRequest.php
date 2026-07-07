<?php

namespace App\Http\Requests;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer && ($this->user()?->can('update', $customer) ?? false);
    }
}
