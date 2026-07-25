<?php

namespace App\Events;

final class CommentAdded extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.comment_added';
    }
}
