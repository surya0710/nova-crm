<?php

namespace App\Http\Requests\Recruitment;

use App\Models\CandidateEvaluation;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitCandidateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CandidateEvaluation::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'interview_round_id' => [
                'required', 'integer',
                Rule::exists('interview_rounds', 'id')->where('organization_id', $org?->id),
            ],
            'interview_participant_id' => [
                'required', 'integer',
                Rule::exists('interview_participants', 'id')->where('organization_id', $org?->id),
            ],
            'evaluation_template_id' => [
                'nullable', 'integer',
                Rule::exists('evaluation_templates', 'id')->where('organization_id', $org?->id),
            ],
            'overall_rating' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'recommendation' => [
                'nullable', 'string',
                Rule::in(array_keys(config('hrms.recruitment.evaluation_recommendations', []))),
            ],
            'strengths' => ['nullable', 'string'],
            'concerns' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'responses' => ['nullable', 'array'],
        ];
    }
}
