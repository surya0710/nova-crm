<?php

namespace App\Events;

final class EvaluationSubmitted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.evaluation_submitted';
    }
}
