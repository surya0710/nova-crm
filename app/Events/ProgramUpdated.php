<?php

namespace App\Events;

final class ProgramUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'program.updated';
    }
}
