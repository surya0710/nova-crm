<?php

namespace App\Policies;

use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sales_orders.view');
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasPermission('sales_orders.view', $salesOrder->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sales_orders.create');
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasPermission('sales_orders.update', $salesOrder->organization)
            && $salesOrder->isEditable();
    }

    public function changeStatus(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasPermission('sales_orders.update', $salesOrder->organization);
    }

    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasPermission('sales_orders.delete', $salesOrder->organization)
            && $salesOrder->isDeletable();
    }

    public function convert(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasPermission('invoices.create', $salesOrder->organization)
            && $user->hasPermission('sales_orders.view', $salesOrder->organization);
    }

    public function send(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasPermission('sales_orders.update', $salesOrder->organization);
    }
}
