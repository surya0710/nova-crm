<?php

namespace App\Http\Requests;

class UpdateApiCustomerRequest extends StoreApiCustomerRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer && ($this->user()?->can('update', $customer) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $field => $fieldRules) {
            if (str_contains($field, '.*')) {
                continue;
            }

            $rules[$field] = array_merge(['sometimes'], $fieldRules);
        }

        return $rules;
    }
}
