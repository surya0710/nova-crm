<?php

namespace App\Events;

final class CompensationRecommended extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'compensation.recommended';
    }
}
