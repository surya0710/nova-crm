<?php

namespace App\Http\Requests\Hrms;

use App\Models\FeedbackCampaign;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddFeedbackParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof FeedbackCampaign
            && ($this->user()?->can('manageParticipants', $campaign) ?? false);
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'subject_employee_id' => [
                'required', 'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'participant_employee_id' => [
                'nullable', 'integer',
                Rule::exists('employees', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'performance_review_id' => [
                'nullable', 'integer',
                Rule::exists('performance_reviews', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'participant_type' => ['required', 'string', Rule::in(array_keys(config('hrms.feedback_participant_types', [])))],
            'external_name' => ['nullable', 'string', 'max:255'],
            'external_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
