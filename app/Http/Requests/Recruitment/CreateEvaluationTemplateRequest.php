<?php

namespace App\Http\Requests\Recruitment;

use App\Models\EvaluationTemplate;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEvaluationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EvaluationTemplate::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'department_id' => [
                'nullable', 'integer',
                Rule::exists('hrms_departments', 'id')->where('organization_id', $org?->id),
            ],
            'designation_id' => [
                'nullable', 'integer',
                Rule::exists('hrms_designations', 'id')->where('organization_id', $org?->id),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sections' => ['nullable', 'array'],
            'sections.*.title' => ['required_with:sections', 'string', 'max:255'],
            'sections.*.weight' => ['nullable', 'integer', 'min:1'],
            'sections.*.questions' => ['nullable', 'array'],
            'sections.*.questions.*.question' => ['required_with:sections.*.questions', 'string'],
            'sections.*.questions.*.question_type' => [
                'required_with:sections.*.questions',
                'string',
                Rule::in(array_keys(config('hrms.recruitment.evaluation_question_types', []))),
            ],
            'sections.*.questions.*.is_required' => ['nullable', 'boolean'],
            'sections.*.questions.*.weight' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
