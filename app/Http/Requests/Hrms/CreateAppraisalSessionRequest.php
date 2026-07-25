<?php

namespace App\Http\Requests\Hrms;

use App\Models\AppraisalSession;
use Illuminate\Foundation\Http\FormRequest;

class CreateAppraisalSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AppraisalSession::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'performance_cycle_id' => ['required', 'integer', 'exists:performance_cycles,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string'],
            'rating_weights' => ['nullable', 'array'],
            'rating_weights.goals' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rating_weights.competencies' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rating_weights.manager_review' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rating_weights.self_review' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rating_weights.feedback_360' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
