<?php

namespace App\Events;

final class PerformanceReviewReviewed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.review.reviewed';
    }
}
