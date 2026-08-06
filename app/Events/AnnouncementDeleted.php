<?php

namespace App\Events;

final class AnnouncementDeleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'announcement.deleted';
    }
}
