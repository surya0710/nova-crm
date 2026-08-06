<?php

namespace App\Http\Requests\Hrms;

use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;

class CreateGoalCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Goal $goal */
        $goal = $this->route('goal');

        return $this->user()?->can('checkin', $goal) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:5000'],
            'progress' => ['nullable', 'string', 'max:5000'],
            'risks' => ['nullable', 'string', 'max:5000'],
            'next_steps' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
