<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lead::class) ?? false;
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'source' => ['required', 'string', 'max:50'],
            'form_type' => ['nullable', 'string', 'max:100'],
            'source_url' => ['nullable', 'string', 'url', 'max:2048'],
            'service_interest' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organization?->id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $fields = ['name', 'email', 'phone', 'source', 'form_type', 'source_url', 'service_interest', 'message'];

        foreach ($fields as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
