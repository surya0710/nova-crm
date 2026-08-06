<?php

namespace App\Events;

final class CandidateLoggedIn extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.candidate_logged_in';
    }
}
