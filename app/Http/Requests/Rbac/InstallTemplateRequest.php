<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class InstallTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('installTemplate', \App\Models\PermissionTemplate::class);
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:permission_templates,id'],
        ];
    }
}
