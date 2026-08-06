<?php

namespace App\Events;

final class InterviewScheduled extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.interview_scheduled';
    }
}
