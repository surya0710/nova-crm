<?php

namespace App\Events;

final class FeedbackSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'feedback.submitted';
    }
}
