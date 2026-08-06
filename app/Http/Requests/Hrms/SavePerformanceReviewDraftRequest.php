<?php

namespace App\Http\Requests\Hrms;

use App\Models\PerformanceReview;
use Illuminate\Foundation\Http\FormRequest;

class SavePerformanceReviewDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PerformanceReview $review */
        $review = $this->route('review');

        return $this->user()?->can('update', $review) ?? false;
    }

    public function rules(): array
    {
        return [
            'overall_comments' => ['nullable', 'string'],
            'development_notes' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'improvement_areas' => ['nullable', 'string'],
            'start' => ['sometimes', 'boolean'],
            'competency_evaluations' => ['sometimes', 'array'],
            'competency_evaluations.*.id' => ['required_with:competency_evaluations', 'integer'],
            'competency_evaluations.*.rating' => ['nullable', 'numeric'],
            'competency_evaluations.*.comments' => ['nullable', 'string'],
            'competency_evaluations.*.reviewer_notes' => ['nullable', 'string'],
            'goal_evaluations' => ['sometimes', 'array'],
            'goal_evaluations.*.id' => ['required_with:goal_evaluations', 'integer'],
            'goal_evaluations.*.comments' => ['nullable', 'string'],
            'goal_evaluations.*.rating' => ['nullable', 'numeric'],
        ];
    }
}
