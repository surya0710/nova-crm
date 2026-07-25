<?php

namespace App\Events;

final class PerformanceReviewStarted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.review.started';
    }
}
