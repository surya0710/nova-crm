<?php

namespace App\Events;

final class CandidateProfileUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.candidate_profile_updated';
    }
}
