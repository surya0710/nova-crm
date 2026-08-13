<?php

namespace App\Events;

final class WfhRequestCancelled extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'wfh.request_cancelled';
    }
}
