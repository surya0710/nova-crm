<?php

namespace App\Events;

final class JobApplied extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.job_applied';
    }
}
