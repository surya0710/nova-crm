<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Product::class) ?? false;
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->where('organization_id', $organization?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'string', Rule::in(array_keys(config('products.types')))],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('products.currencies')))],
            'unit' => ['nullable', 'string', Rule::in(array_keys(config('products.units')))],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(array_keys(config('products.statuses')))],
        ];
    }
}
