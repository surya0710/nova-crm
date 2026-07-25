<?php

namespace App\Http\Requests\Hrms;

use App\Models\FeedbackCampaign;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFeedbackCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FeedbackCampaign::class) ?? false;
    }

    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'performance_cycle_id' => [
                'required', 'integer',
                Rule::exists('performance_cycles', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'feedback_template_id' => [
                'required', 'integer',
                Rule::exists('feedback_templates', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(array_keys(config('hrms.feedback_campaign_statuses', [])))],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_anonymous')) {
            $this->merge(['is_anonymous' => $this->boolean('is_anonymous')]);
        }
    }
}
