<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = app(\App\Services\TenantContext::class)->get();

        return $organization && ($this->user()?->can('update', $organization) ?? false);
    }

    protected function mailEnabled(): bool
    {
        return filter_var($this->input('mail_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function mailUsesSmtp(): bool
    {
        return $this->input('mail_driver', 'smtp') === 'smtp';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'tax_name' => ['nullable', 'string', 'max:50'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'currency' => ['required', 'string', 'size:3'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'industry_type' => ['nullable', 'string', Rule::in(array_keys(config('terminology.industries')))],
            'terminology' => ['nullable', 'array'],
            'terminology.*' => ['nullable', 'string', 'max:50'],
            'mail_enabled' => ['nullable', 'boolean'],
            'mail_driver' => ['nullable', 'string', Rule::in(array_keys(config('organization_mail.drivers')))],
            'mail_host' => [
                Rule::requiredIf(fn () => $this->mailEnabled() && $this->mailUsesSmtp()),
                'nullable', 'string', 'max:255',
            ],
            'mail_port' => [
                Rule::requiredIf(fn () => $this->mailEnabled() && $this->mailUsesSmtp()),
                'nullable', 'integer', 'min:1', 'max:65535',
            ],
            'mail_encryption' => ['nullable', 'string', Rule::in(array_keys(config('organization_mail.encryptions')))],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => [
                Rule::requiredIf(fn () => $this->mailEnabled()),
                'nullable', 'email', 'max:255',
            ],
            'mail_from_name' => [
                Rule::requiredIf(fn () => $this->mailEnabled()),
                'nullable', 'string', 'max:255',
            ],
        ];
    }
}
