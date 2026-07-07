<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Opportunity::class) ?? false;
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
        ];
    }
}
