<?php

namespace App\Events;

final class InterviewCancelled extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.interview_cancelled';
    }
}
