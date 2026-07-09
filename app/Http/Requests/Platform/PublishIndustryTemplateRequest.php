<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class PublishIndustryTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('platform')->user()?->hasPermission('platform.industry_templates.publish') ?? false;
    }

    public function rules(): array
    {
        return [
            'changelog' => ['nullable', 'string'],
        ];
    }
}
