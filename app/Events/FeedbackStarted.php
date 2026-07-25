<?php

namespace App\Events;

final class FeedbackStarted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'feedback.started';
    }
}
