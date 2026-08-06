<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecruitmentSavedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\RecruitmentSavedReport::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_name' => ['required', 'string', 'max:255'],
            'report_type' => ['required', 'string', Rule::in(array_keys(config('hrms.recruitment.report_types', [])))],
            'period' => ['nullable', 'string', Rule::in(array_keys(config('hrms.recruitment.analytics.periods', [])))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'leaderboard_period' => ['nullable', 'string'],
            'is_shared' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{report_name: string, report_type: string, filters_json: array<string, mixed>, is_shared: bool}
     */
    public function payload(): array
    {
        return [
            'report_name' => $this->string('report_name')->toString(),
            'report_type' => $this->string('report_type')->toString(),
            'filters_json' => $this->only(['period', 'from', 'to', 'leaderboard_period']),
            'is_shared' => $this->boolean('is_shared'),
        ];
    }
}
