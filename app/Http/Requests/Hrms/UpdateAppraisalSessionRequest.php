<?php

namespace App\Http\Requests\Hrms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppraisalSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('session'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'performance_cycle_id' => ['sometimes', 'integer', 'exists:performance_cycles,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'status' => ['nullable', 'string'],
            'rating_weights' => ['nullable', 'array'],
        ];
    }
}
