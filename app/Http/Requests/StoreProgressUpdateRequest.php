<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('createProgress', $project) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Project|null $project */
        $project = $this->route('project');
        $organizationId = $project?->organization_id;

        return [
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'summary' => ['required', 'string', 'max:5000'],
            'blockers' => ['nullable', 'string', 'max:5000'],
            'next_steps' => ['nullable', 'string', 'max:5000'],
            'milestone_id' => [
                'nullable',
                'integer',
                Rule::exists('project_milestones', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('project_id', $project?->id),
            ],
            'metadata' => ['nullable', 'array'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('milestone_id') && $this->input('milestone_id') === '') {
            $this->merge(['milestone_id' => null]);
        }
    }
}
