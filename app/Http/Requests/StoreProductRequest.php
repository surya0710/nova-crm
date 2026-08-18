<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
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
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('products.currencies')))],
            'unit' => ['nullable', 'string', Rule::in(array_keys(config('products.units')))],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'hsn_sac' => ['nullable', 'string', 'max:20'],
            'tax_inclusive' => ['nullable', 'boolean'],
            'cess_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'product_category_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where('organization_id', $organization?->id),
            ],
            'status' => ['required', 'string', Rule::in(array_keys(config('products.statuses')))],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sku') && is_string($this->input('sku'))) {
            $sku = trim($this->input('sku'));
            $this->merge(['sku' => $sku === '' ? null : $sku]);
        }

        if ($this->has('hsn_sac') && is_string($this->input('hsn_sac'))) {
            $hsn = strtoupper(trim($this->input('hsn_sac')));
            $this->merge(['hsn_sac' => $hsn === '' ? null : $hsn]);
        }

        $this->merge([
            'tax_inclusive' => $this->boolean('tax_inclusive'),
        ]);
    }
}
