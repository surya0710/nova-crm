<?php

namespace App\Events;

final class WfhRequestSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'wfh.request_submitted';
    }
}
