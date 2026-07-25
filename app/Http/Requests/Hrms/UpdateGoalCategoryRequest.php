<?php

namespace App\Http\Requests\Hrms;

use App\Models\GoalCategory;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoalCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var GoalCategory $category */
        $category = $this->route('goal_category') ?? $this->route('category');

        return $this->user()?->can('update', $category) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $org = app(TenantContext::class)->get();
        /** @var GoalCategory $category */
        $category = $this->route('goal_category') ?? $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('goal_categories', 'code')
                    ->where('organization_id', $org?->id)
                    ->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }
}
