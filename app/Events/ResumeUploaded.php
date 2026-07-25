<?php

namespace App\Events;

final class ResumeUploaded extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.resume_uploaded';
    }
}
