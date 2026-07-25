<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class CreateOfferTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\OfferTemplate::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:hrms_departments,id'],
            'designation_id' => ['nullable', 'integer', 'exists:hrms_designations,id'],
            'employment_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('hrms.employment_types', [])))],
            'is_active' => ['sometimes', 'boolean'],
            'template_content' => ['required', 'string'],
        ];
    }
}
