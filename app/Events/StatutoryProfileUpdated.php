<?php

namespace App\Events;

final class StatutoryProfileUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'statutory.profile.updated';
    }
}
