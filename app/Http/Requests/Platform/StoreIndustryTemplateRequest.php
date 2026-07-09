<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndustryTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('platform')->user()?->hasPermission('platform.industry_templates.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'visibility' => ['required', Rule::in(array_keys(config('industry_templates.visibility')))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'draft_payload' => ['nullable', 'string'],
        ];
    }
}
