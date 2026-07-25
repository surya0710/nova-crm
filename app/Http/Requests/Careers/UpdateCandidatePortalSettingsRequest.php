<?php

namespace App\Http\Requests\Careers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidatePortalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('recruitment.portal.settings') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'portal_enabled' => ['sometimes', 'boolean'],
            'allow_guest_apply' => ['sometimes', 'boolean'],
            'require_login_to_apply' => ['sometimes', 'boolean'],
        ];
    }
}
