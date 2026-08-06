<?php

namespace App\Events;

final class StatutoryRuleChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'statutory.rule.changed';
    }
}
