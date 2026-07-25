<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecruitmentAnalyticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('recruitment.analytics.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.analytics.periods', [])))],
            'from' => ['nullable', 'date', 'required_if:period,custom'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'required_if:period,custom'],
            'leaderboard_period' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.analytics.leaderboard_periods', [])))],
            'department_id' => ['nullable', 'integer'],
            'section' => ['nullable', 'string', Rule::in([
                'funnel', 'sources', 'recruiters', 'candidates', 'openings', 'departments', 'trends', 'time',
            ])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->only(['period', 'from', 'to', 'leaderboard_period', 'department_id']);
    }
}
