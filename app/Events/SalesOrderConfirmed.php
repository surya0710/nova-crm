<?php

namespace App\Events;

final class SalesOrderConfirmed extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'sales_order.confirmed';
    }
}
