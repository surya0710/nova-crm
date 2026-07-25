<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('generateReports', $project) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(array_keys(config('projects.report_types', [])))],
            'format' => ['required', 'string', Rule::in(array_keys(config('projects.report_formats', [])))],
            'filters' => ['nullable', 'array'],
        ];
    }
}
