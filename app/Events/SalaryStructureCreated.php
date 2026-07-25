<?php

namespace App\Events;

final class SalaryStructureCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'salary_structure.created';
    }
}
