<?php

namespace App\Events;

final class AnnouncementUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'announcement.updated';
    }
}
