<?php

namespace App\Events;

final class AppraisalClosed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'appraisal.closed';
    }
}
