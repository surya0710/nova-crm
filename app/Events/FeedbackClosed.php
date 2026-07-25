<?php

namespace App\Events;

final class FeedbackClosed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'feedback.closed';
    }
}
