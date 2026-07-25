<?php

namespace App\Events;

final class ApplicationWithdrawn extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.application_withdrawn';
    }
}
