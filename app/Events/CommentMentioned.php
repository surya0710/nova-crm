<?php

namespace App\Events;

final class CommentMentioned extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'comment.mentioned';
    }
}
