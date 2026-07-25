<?php

namespace App\Events;

final class CandidateRecommended extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.candidate_recommended';
    }
}
