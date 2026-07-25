<?php

namespace App\Http\Requests\Hrms;

use App\Models\PerformanceCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePerformanceCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PerformanceCycle::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cycle_type' => ['required', Rule::in(array_keys(config('hrms.performance_cycle_types', [])))],
            'status' => ['sometimes', Rule::in(['draft', 'scheduled'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
