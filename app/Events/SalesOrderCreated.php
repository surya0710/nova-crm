<?php

namespace App\Events;

final class SalesOrderCreated extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'sales_order.created';
    }
}
