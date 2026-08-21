<?php

namespace App\Http\Requests;

use App\Models\Opportunity;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Opportunity::class) ?? false;
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'title' => ['required', 'string', 'max:255'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('organization_id', $organization?->id),
            ],
            'lead_id' => [
                'nullable',
                'integer',
                Rule::exists('leads', 'id')->where('organization_id', $organization?->id),
            ],
            'stage' => ['required', 'string', Rule::in(config('pipeline.open_stages'))],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organization?->id),
            ],
            'source' => ['nullable', 'string', Rule::in(array_keys(config('customers.sources')))],
            'competitor' => ['nullable', 'string', 'max:255'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_id' => ['required_with:contacts', 'integer', 'exists:contacts,id'],
            'contacts.*.role' => ['nullable', 'string', Rule::in(array_keys(config('pipeline.contact_roles')))],
            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'products.*.name' => ['nullable', 'string', 'max:255'],
            'products.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'products.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
