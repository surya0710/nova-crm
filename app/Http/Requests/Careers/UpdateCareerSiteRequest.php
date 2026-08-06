<?php

namespace App\Http\Requests\Careers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCareerSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('recruitment.careers.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'about_us' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'culture' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'social_links' => ['nullable', 'array'],
            'social_links.linkedin' => ['nullable', 'url', 'max:255'],
            'social_links.twitter' => ['nullable', 'url', 'max:255'],
            'social_links.facebook' => ['nullable', 'url', 'max:255'],
            'recruitment_contact_email' => ['nullable', 'email', 'max:255'],
            'recruitment_contact_phone' => ['nullable', 'string', 'max:30'],
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'custom_footer' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
