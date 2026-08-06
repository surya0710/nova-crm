<?php

namespace App\Events;

final class SalaryStructureUpdated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'salary_structure.updated';
    }
}
