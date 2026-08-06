<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ProjectRisk;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if ($project instanceof Project) {
            return $this->user()?->can('createRisks', $project) ?? false;
        }

        return $this->user()?->can('create', ProjectRisk::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
            'probability' => ['nullable', 'integer', 'min:1', 'max:5'],
            'impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'mitigation_plan' => ['nullable', 'string', 'max:10000'],
            'contingency_plan' => ['nullable', 'string', 'max:10000'],
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
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
