<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead
            && ($this->user()?->can('convert', $lead) ?? false);
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'create_opportunity' => ['sometimes', 'boolean'],
            'existing_customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('organization_id', $organization?->id),
            ],
            'force_create' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_opportunity' => $this->boolean('create_opportunity', true),
            'force_create' => $this->boolean('force_create'),
        ]);
    }
}
