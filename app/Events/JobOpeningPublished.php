<?php

namespace App\Events;

final class JobOpeningPublished extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.opening_published';
    }
}
