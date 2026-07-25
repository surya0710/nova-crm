<?php

namespace App\Events;

final class CandidateCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.candidate_created';
    }
}
