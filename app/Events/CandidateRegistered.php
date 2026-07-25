<?php

namespace App\Events;

final class CandidateRegistered extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.candidate_registered';
    }
}
