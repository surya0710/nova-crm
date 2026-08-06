<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveMarketingProviderAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_id' => ['nullable', 'string', 'max:64'],
            'ad_account_id' => ['nullable', 'string', 'max:64'],
            'page_id' => ['nullable', 'string', 'max:64'],
            'pixel_id' => ['nullable', 'string', 'max:64'],
            'lead_form_ids' => ['nullable', 'array'],
            'lead_form_ids.*' => ['string', 'max:64'],
            'customer_id' => ['nullable', 'string', 'max:64'],
            'conversion_action_ids' => ['nullable', 'array'],
            'conversion_action_ids.*' => ['string', 'max:64'],
        ];
    }

    /**
     * Provider adapters read only the keys they understand and return a
     * sanitized provider-specific configuration payload.
     *
     * @return array<string, mixed>
     */
    public function selection(): array
    {
        return [
            'business_id' => $this->input('business_id'),
            'ad_account_id' => $this->input('ad_account_id'),
            'page_id' => $this->input('page_id'),
            'pixel_id' => $this->input('pixel_id'),
            'lead_form_ids' => $this->idList('lead_form_ids'),
            'customer_id' => $this->input('customer_id'),
            'conversion_action_ids' => $this->idList('conversion_action_ids'),
        ];
    }

    /**
     * @return list<string>
     */
    protected function idList(string $key): array
    {
        return array_values(array_filter(
            array_map('strval', $this->input($key, [])),
            fn (string $id) => $id !== ''
        ));
    }
}
