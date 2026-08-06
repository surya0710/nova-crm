<?php

namespace App\Http\Requests\Recruitment;

use App\Models\EvaluationTemplate;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInterviewStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InterviewStage::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'slug' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('interview_stages', 'slug')->where('organization_id', $org?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
