<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('dashboard.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'configuration' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
