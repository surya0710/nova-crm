<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('offer_template')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:hrms_departments,id'],
            'designation_id' => ['nullable', 'integer', 'exists:hrms_designations,id'],
            'employment_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('hrms.employment_types', [])))],
            'is_active' => ['sometimes', 'boolean'],
            'template_content' => ['sometimes', 'string'],
        ];
    }
}
