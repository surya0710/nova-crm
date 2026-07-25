<?php

namespace App\Http\Requests\Hrms;

use App\Models\PerformanceReview;

class SubmitPerformanceReviewRequest extends SavePerformanceReviewDraftRequest
{
    public function authorize(): bool
    {
        /** @var PerformanceReview $review */
        $review = $this->route('review');

        return $this->user()?->can('submit', $review) ?? false;
    }
}
