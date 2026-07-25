<?php

namespace App\Events;

final class AppraisalSessionCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'appraisal.session.created';
    }
}
