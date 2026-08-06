<?php

namespace App\Events;

final class FeedbackRequestSent extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'feedback.request.sent';
    }
}
