<?php

namespace App\Events;

final class PerformanceTemplateCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.template.created';
    }
}
