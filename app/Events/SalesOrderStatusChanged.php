<?php

namespace App\Events;

final class SalesOrderStatusChanged extends WorkflowDomainEvent
{
    public function trigger(): string
    {
        return 'sales_order.status_changed';
    }
}
