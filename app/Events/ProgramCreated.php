<?php

namespace App\Events;

final class ProgramCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'program.created';
    }
}
