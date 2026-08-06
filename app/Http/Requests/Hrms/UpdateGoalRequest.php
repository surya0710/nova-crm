<?php

namespace App\Http\Requests\Hrms;

use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Goal $goal */
        $goal = $this->route('goal');

        return $this->user()?->can('update', $goal) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'target_value' => ['nullable', 'numeric'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
