<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\User;

class CustomerTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('customers.view');
    }

    public function view(User $user, CustomerTicket $ticket): bool
    {
        return $user->hasPermission('customers.view', $ticket->organization);
    }

    public function create(User $user, ?Customer $customer = null): bool
    {
        if ($customer) {
            return $user->hasPermission('customers.update', $customer->organization);
        }

        return $user->hasPermission('customers.update');
    }

    public function update(User $user, CustomerTicket $ticket): bool
    {
        return $user->hasPermission('customers.update', $ticket->organization);
    }

    public function delete(User $user, CustomerTicket $ticket): bool
    {
        return $user->hasPermission('customers.update', $ticket->organization)
            || $user->hasPermission('customers.delete', $ticket->organization);
    }
}
