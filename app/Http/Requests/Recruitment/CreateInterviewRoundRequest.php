<?php

namespace App\Http\Requests\Recruitment;

use App\Models\EvaluationTemplate;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInterviewRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InterviewRound::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'job_application_id' => [
                'required', 'integer',
                Rule::exists('job_applications', 'id')->where('organization_id', $org?->id),
            ],
            'interview_stage_id' => [
                'required', 'integer',
                Rule::exists('interview_stages', 'id')->where('organization_id', $org?->id),
            ],
            'round_number' => ['nullable', 'integer', 'min:1'],
            'interview_type' => ['required', 'string', Rule::in(array_keys(config('hrms.recruitment.interview_types', [])))],
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.interview_round_statuses', [])))],
            'evaluation_template_id' => [
                'nullable', 'integer',
                Rule::exists('evaluation_templates', 'id')->where('organization_id', $org?->id),
            ],
            'participants' => ['nullable', 'array'],
            'participants.*.participant_type' => ['required_with:participants', 'string', Rule::in(array_keys(config('hrms.recruitment.participant_types', [])))],
            'participants.*.employee_id' => ['nullable', 'integer'],
            'participants.*.name' => ['nullable', 'string', 'max:255'],
            'participants.*.email' => ['nullable', 'email', 'max:255'],
            'participants.*.role' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.participant_roles', [])))],
        ];
    }
}
