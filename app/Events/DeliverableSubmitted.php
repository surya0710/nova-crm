<?php

namespace App\Events;

final class DeliverableSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'deliverable.submitted';
    }
}
