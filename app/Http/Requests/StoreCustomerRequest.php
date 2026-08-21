<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCustomerTaxProfile;
use App\Models\Customer;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerRequest extends FormRequest
{
    use ValidatesCustomerTaxProfile;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(array_keys(config('customers.statuses')))],
            'type' => ['nullable', 'string', Rule::in(array_keys(config('customers.types')))],
            'lifecycle_stage' => ['nullable', 'string', Rule::in(array_keys(config('customers.lifecycle_stages')))],
            'segment' => ['nullable', 'string', Rule::in(array_keys(config('customers.segments')))],
            'source' => ['nullable', 'string', Rule::in(array_keys(config('customers.sources')))],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organization?->id),
            ],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            ...$this->customerTaxProfileRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateGstinPanConsistency($validator));
    }

    protected function prepareForValidation(): void
    {
        foreach (['address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim($this->input($field));
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }

        if ($this->has('tags') && is_string($this->tags)) {
            $tags = trim($this->tags);

            $this->merge([
                'tags' => $tags === '' ? null : array_values(array_filter(array_map('trim', explode(',', $tags)))),
            ]);
        }

        $this->prepareCustomerTaxProfile();
    }
}
