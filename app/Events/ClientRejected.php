<?php

namespace App\Events;

final class ClientRejected extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'client.rejected';
    }
}
