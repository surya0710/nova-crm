<?php

namespace App\Http\Requests;

use App\Services\QuotationCalculationService;
use App\Services\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

trait ValidatesQuotationItems
{
    /**
     * @return array<string, mixed>
     */
    protected function quotationFieldRules(bool $isUpdate = false): array
    {
        $organization = app(TenantContext::class)->get();

        $rules = [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('organization_id', $organization?->id),
            ],
            'opportunity_id' => [
                'nullable',
                'integer',
                Rule::exists('opportunities', 'id')->where('organization_id', $organization?->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('quotations.currencies')))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('organization_id', $organization?->id),
            ],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.cess_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_inclusive' => ['nullable', 'boolean'],
            'items.*.sku' => ['nullable', 'string', 'max:50'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.hsn_sac' => ['nullable', 'string', 'max:20'],
            'pricing_mode' => ['nullable', 'string', Rule::in(array_keys(config('tax.pricing_modes')))],
            'tax_treatment' => ['nullable', 'string', Rule::in(array_keys(config('tax.treatments')))],
            'place_of_supply' => ['nullable', 'string', Rule::in(array_keys(config('tax.states')))],
            'shipping_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'terms' => ['nullable', 'string', 'max:10000'],
        ];

        if ($isUpdate) {
            return $rules;
        }

        $rules['status'] = ['required', 'string', Rule::in(['draft'])];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $items = $this->input('items', []);

            try {
                app(QuotationCalculationService::class)->validateItems($items);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }
}
