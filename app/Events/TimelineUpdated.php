<?php

namespace App\Events;

final class TimelineUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'project.timeline.updated';
    }
}
