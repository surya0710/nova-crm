<?php

namespace App\Events;

final class RequisitionApproved extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'recruitment.requisition_approved';
    }
}
