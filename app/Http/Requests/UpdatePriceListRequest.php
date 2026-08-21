<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\TenantContext;
use Illuminate\Validation\Rule;

class UpdatePriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $list = $this->route('price_list');

        return $list && ($this->user()?->can('update', $list) ?? false);
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('price_lists.currencies')))],
            'is_default' => ['sometimes', 'boolean'],
            'status' => ['required', 'string', Rule::in(array_keys(config('price_lists.statuses')))],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', Rule::exists('customers', 'id')->where('organization_id', $organization?->id)],
            'customer_priorities' => ['nullable', 'array'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('organization_id', $organization?->id)],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.min_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.max_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_inclusive' => ['nullable', 'boolean'],
            'items.*.starts_at' => ['nullable', 'date'],
            'items.*.ends_at' => ['nullable', 'date'],
        ];
    }
}
