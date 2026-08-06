<?php

namespace App\Events;

final class DiscussionCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'discussion.created';
    }
}
