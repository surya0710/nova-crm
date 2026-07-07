<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product && ($this->user()?->can('update', $product) ?? false);
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $product = $this->route('product');

        return [
            ...parent::rules(),
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku')
                    ->where('organization_id', $organization?->id)
                    ->ignore($product?->id),
            ],
        ];
    }
}
