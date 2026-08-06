<?php

namespace App\Events;

final class TimeLogged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'task.time_logged';
    }
}
