<?php

namespace App\Events;

final class PerformanceReviewAssigned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.review.assigned';
    }
}
