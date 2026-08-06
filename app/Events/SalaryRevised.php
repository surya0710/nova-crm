<?php

namespace App\Events;

final class SalaryRevised extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'salary.revised';
    }
}
