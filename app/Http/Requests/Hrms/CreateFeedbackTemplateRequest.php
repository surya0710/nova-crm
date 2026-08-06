<?php

namespace App\Http\Requests\Hrms;

use App\Models\FeedbackTemplate;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFeedbackTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FeedbackTemplate::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'questions' => ['sometimes', 'array'],
            'questions.*.question_type' => ['required_with:questions', 'string', Rule::in(array_keys(config('hrms.feedback_question_types', [])))],
            'questions.*.competency_id' => [
                'nullable', 'integer',
                Rule::exists('competencies', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'questions.*.question_text' => ['required_with:questions', 'string'],
            'questions.*.help_text' => ['nullable', 'string'],
            'questions.*.scale_min' => ['nullable', 'integer', 'min:1'],
            'questions.*.scale_max' => ['nullable', 'integer', 'max:10'],
            'questions.*.sort_order' => ['nullable', 'integer'],
            'questions.*.is_required' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }
}
