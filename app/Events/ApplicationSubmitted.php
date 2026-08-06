<?php

namespace App\Events;

final class ApplicationSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.application_submitted';
    }
}
