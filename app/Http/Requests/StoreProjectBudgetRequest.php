<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ProjectBudget;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if ($project instanceof Project) {
            return $this->user()?->can('manageBudgets', $project) ?? false;
        }

        return $this->user()?->can('create', ProjectBudget::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'metadata' => ['nullable', 'array'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.budget_category_id' => ['nullable', 'integer'],
            'items.*.category_slug' => ['nullable', 'string', 'max:100'],
            'items.*.planned' => ['nullable', 'numeric'],
            'items.*.actual' => ['nullable', 'numeric'],
            'items.*.forecast' => ['nullable', 'numeric'],
            'items.*.currency' => ['nullable', 'string', 'size:3'],
            'items.*.notes' => ['nullable', 'string', 'max:5000'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ];
    }
}
