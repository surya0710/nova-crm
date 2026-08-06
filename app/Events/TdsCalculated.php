<?php

namespace App\Events;

final class TdsCalculated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'tds.calculated';
    }
}
