<?php

namespace App\Events;

final class AnnouncementCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'announcement.created';
    }
}
