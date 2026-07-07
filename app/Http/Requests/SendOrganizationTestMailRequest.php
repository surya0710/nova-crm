<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOrganizationTestMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = app(\App\Services\TenantContext::class)->get();

        return $organization && ($this->user()?->can('update', $organization) ?? false);
    }

    public function rules(): array
    {
        return [
            'test_email' => ['required', 'email', 'max:255'],
        ];
    }
}
