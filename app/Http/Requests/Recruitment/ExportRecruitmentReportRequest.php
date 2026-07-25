<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRecruitmentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('recruitment.reports.export') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(array_keys(config('hrms.recruitment.report_types', [])))],
            'format' => ['required', 'string', Rule::in(['csv', 'xlsx', 'pdf'])],
            'period' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.analytics.periods', [])))],
            'from' => ['nullable', 'date', 'required_if:period,custom'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'required_if:period,custom'],
            'leaderboard_period' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->only(['period', 'from', 'to', 'leaderboard_period']);
    }
}
