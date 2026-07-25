<?php

namespace App\Events;

final class PerformanceReviewSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.review.submitted';
    }
}
