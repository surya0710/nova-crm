<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class SaveDashboardLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'layout' => ['required', 'array'],
            'layout.*.widget_id' => ['required', 'integer', 'exists:dashboard_widgets,id'],
            'layout.*.position_x' => ['nullable', 'integer', 'min:0', 'max:11'],
            'layout.*.position_y' => ['nullable', 'integer', 'min:0'],
            'layout.*.width' => ['nullable', 'integer', 'min:1', 'max:12'],
            'layout.*.height' => ['nullable', 'integer', 'min:1', 'max:12'],
            'layout.*.is_visible' => ['nullable', 'boolean'],
            'layout.*.custom_configuration' => ['nullable', 'array'],
        ];
    }
}
