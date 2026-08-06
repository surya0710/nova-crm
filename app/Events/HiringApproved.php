<?php

namespace App\Events;

final class HiringApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.hiring_approved';
    }
}
