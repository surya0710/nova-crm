<?php

namespace App\Http\Requests\Platform;

use App\Models\IndustryTemplateVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('platform')->user()?->hasPermission('platform.organizations.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:organizations,slug'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'plan' => ['required', Rule::in(array_keys(config('platform.plans')))],
            'status' => ['required', Rule::in(array_keys(config('platform.organization_statuses')))],
            'timezone' => ['nullable', 'timezone'],
            'currency' => ['nullable', 'string', 'size:3'],
            'tax_name' => ['nullable', 'string', 'max:255'],
            'template_version_id' => ['nullable', 'integer', Rule::exists('industry_template_versions', 'id')],
            'owner_name' => ['nullable', 'required_with:owner_email', 'string', 'max:255'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'owner_password' => ['nullable', 'required_with:owner_email', 'string', 'min:8'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('template_version_id')) {
                return;
            }

            $version = IndustryTemplateVersion::query()
                ->with('template')
                ->find($this->integer('template_version_id'));

            if (! $version || $version->status !== 'published' || ! $version->template?->isSelectable()) {
                $validator->errors()->add('template_version_id', __('Select an active published industry template.'));
            }
        });
    }
}
