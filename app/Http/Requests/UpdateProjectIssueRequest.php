<?php

namespace App\Http\Requests;

use App\Models\ProjectIssue;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $issue = $this->route('issue') ?? $this->route('project_issue');

        return $issue instanceof ProjectIssue
            && ($this->user()?->can('update', $issue) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high', 'critical'])],
            'severity' => ['nullable', 'string', Rule::in(['low', 'medium', 'high', 'critical'])],
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', 'string', 'max:50'],
            'resolution' => ['nullable', 'string', 'max:10000'],
            'root_cause' => ['nullable', 'string', 'max:10000'],
            'due_date' => ['nullable', 'date'],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'portfolio_id' => [
                'nullable',
                'integer',
                Rule::exists('portfolios', 'id')->where('organization_id', $organizationId),
            ],
            'program_id' => [
                'nullable',
                'integer',
                Rule::exists('programs', 'id')->where('organization_id', $organizationId),
            ],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
