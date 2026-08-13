<?php

namespace App\Events;

final class WfhRequestApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'wfh.request_approved';
    }
}
