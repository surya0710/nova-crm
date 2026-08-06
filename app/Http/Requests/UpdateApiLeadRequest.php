<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApiLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead && ($this->user()?->can('update', $lead) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'source' => ['sometimes', 'required', 'string', Rule::in(array_keys(config('leads.sources')))],
            'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'budget' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'priority' => ['sometimes', 'required', 'string', Rule::in(array_keys(config('leads.priorities')))],
            'status' => ['sometimes', 'required', 'string', Rule::in(array_keys(config('leads.statuses')))],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organization?->id),
            ],
            'custom_fields' => ['sometimes', 'nullable', 'array'],
            'custom_fields.*' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'name', 'company', 'email', 'phone', 'source', 'industry',
            'address_line_1', 'city', 'state', 'country', 'postal_code',
        ] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim($this->input($field));
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
    }
}
