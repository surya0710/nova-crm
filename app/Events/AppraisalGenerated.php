<?php

namespace App\Events;

final class AppraisalGenerated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'appraisal.generated';
    }
}
