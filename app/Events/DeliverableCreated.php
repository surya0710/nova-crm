<?php

namespace App\Events;

final class DeliverableCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'deliverable.created';
    }
}
