<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends StoreProductCategoryRequest
{
    public function authorize(): bool
    {
        $category = $this->route('productCategory');

        return $category && ($this->user()?->can('update', $category) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();
        $category = $this->route('productCategory');

        return [
            ...parent::rules(),
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_categories', 'slug')
                    ->where('organization_id', $organization?->id)
                    ->ignore($category?->id),
            ],
        ];
    }
}
