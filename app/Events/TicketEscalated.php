<?php

namespace App\Events;

final class TicketEscalated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'ticket.escalated';
    }
}
