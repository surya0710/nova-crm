<?php

namespace App\Events;

final class PerformanceReviewClosed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.review.closed';
    }
}
