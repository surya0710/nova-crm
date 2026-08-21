<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('customers.view');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->hasPermission('customers.view', $contact->organization);
    }

    public function create(User $user, ?Customer $customer = null): bool
    {
        if ($customer) {
            return $user->hasPermission('customers.update', $customer->organization)
                || $user->hasPermission('customers.create', $customer->organization);
        }

        return $user->hasPermission('customers.update') || $user->hasPermission('customers.create');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->hasPermission('customers.update', $contact->organization);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->hasPermission('customers.update', $contact->organization)
            || $user->hasPermission('customers.delete', $contact->organization);
    }
}
