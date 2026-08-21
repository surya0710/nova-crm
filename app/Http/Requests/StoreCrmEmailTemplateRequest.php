<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = app(TenantContext::class)->get();

        return $organization && (
            ($this->user()?->can('update', $organization) ?? false)
            || ($this->user()?->hasPermission('email_templates.manage', $organization) ?? false)
        );
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'category' => ['required', 'string', Rule::in(array_keys(config('crm_email.categories', [])))],
            'is_active' => ['nullable', 'boolean'],
            'available_modules' => ['nullable', 'array'],
            'available_modules.*' => ['string', Rule::in(array_keys(config('crm_email.modules', [])))],
        ];
    }
}
