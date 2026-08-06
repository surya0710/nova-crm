<?php

namespace App\Events;

final class InterviewCompleted extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.interview_completed';
    }
}
