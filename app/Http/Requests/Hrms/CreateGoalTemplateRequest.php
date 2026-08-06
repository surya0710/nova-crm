<?php

namespace App\Http\Requests\Hrms;

use App\Models\GoalTemplate;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateGoalTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', GoalTemplate::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();

        return [
            'goal_category_id' => [
                'nullable', 'integer',
                Rule::exists('goal_categories', 'id')->where('organization_id', $org?->id)->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'goal_type' => ['required', 'string', Rule::in(array_keys(config('hrms.goal_types', [])))],
            'default_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'measurement_type' => ['required', 'string', Rule::in(array_keys(config('hrms.goal_measurement_types', [])))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }
}
