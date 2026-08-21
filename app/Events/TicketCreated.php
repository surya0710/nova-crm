<?php

namespace App\Events;

final class TicketCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'ticket.created';
    }
}
