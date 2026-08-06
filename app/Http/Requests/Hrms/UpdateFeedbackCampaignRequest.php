<?php

namespace App\Http\Requests\Hrms;

use App\Models\FeedbackCampaign;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeedbackCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof FeedbackCampaign
            && ($this->user()?->can('update', $campaign) ?? false);
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'performance_cycle_id' => [
                'sometimes', 'integer',
                Rule::exists('performance_cycles', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'feedback_template_id' => [
                'sometimes', 'integer',
                Rule::exists('feedback_templates', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(array_keys(config('hrms.feedback_campaign_statuses', [])))],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_anonymous')) {
            $this->merge(['is_anonymous' => $this->boolean('is_anonymous')]);
        }
    }
}
