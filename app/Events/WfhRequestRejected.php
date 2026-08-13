<?php

namespace App\Events;

final class WfhRequestRejected extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'wfh.request_rejected';
    }
}
