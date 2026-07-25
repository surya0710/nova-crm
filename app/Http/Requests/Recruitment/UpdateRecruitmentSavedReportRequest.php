<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecruitmentSavedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('recruitment_saved_report');

        return $report && ($this->user()?->can('update', $report) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_name' => ['sometimes', 'required', 'string', 'max:255'],
            'report_type' => ['sometimes', 'required', 'string', Rule::in(array_keys(config('hrms.recruitment.report_types', [])))],
            'period' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.analytics.periods', [])))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'leaderboard_period' => ['nullable', 'string'],
            'is_shared' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = [];
        if ($this->filled('report_name')) {
            $data['report_name'] = $this->string('report_name')->toString();
        }
        if ($this->filled('report_type')) {
            $data['report_type'] = $this->string('report_type')->toString();
        }
        if ($this->has('is_shared')) {
            $data['is_shared'] = $this->boolean('is_shared');
        }
        if ($this->hasAny(['period', 'from', 'to', 'leaderboard_period'])) {
            $data['filters_json'] = array_merge(
                $this->route('recruitment_saved_report')->filters_json ?? [],
                $this->only(['period', 'from', 'to', 'leaderboard_period']),
            );
        }

        return $data;
    }
}
