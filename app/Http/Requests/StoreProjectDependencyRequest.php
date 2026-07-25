<?php

namespace App\Http\Requests;

use App\Models\ProjectDependency;
use App\Services\DependencyGraphService;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectDependencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProjectDependency::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();
        $types = array_keys(config('tasks.dependency_types', []));
        if ($types === []) {
            $types = DependencyGraphService::DEPENDENCY_TYPES;
        }

        return [
            'predecessor_project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'successor_project_id' => [
                'required',
                'integer',
                'different:predecessor_project_id',
                Rule::exists('projects', 'id')->where('organization_id', $organizationId),
            ],
            'dependency_type' => ['nullable', 'string', Rule::in($types)],
            'lag_days' => ['nullable', 'integer', 'min:-3650', 'max:3650'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
