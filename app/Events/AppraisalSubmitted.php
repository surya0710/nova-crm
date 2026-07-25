<?php

namespace App\Events;

final class AppraisalSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'appraisal.submitted';
    }
}
