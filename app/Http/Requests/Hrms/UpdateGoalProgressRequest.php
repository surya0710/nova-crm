<?php

namespace App\Http\Requests\Hrms;

use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Goal $goal */
        $goal = $this->route('goal');

        return $this->user()?->can('updateProgress', $goal) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'progress_value' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
