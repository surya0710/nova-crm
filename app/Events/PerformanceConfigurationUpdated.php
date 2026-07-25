<?php

namespace App\Events;

final class PerformanceConfigurationUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'performance.configuration.updated';
    }
}
