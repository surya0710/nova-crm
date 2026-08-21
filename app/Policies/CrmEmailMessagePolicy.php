<?php

namespace App\Policies;

use App\Models\CrmEmailMessage;
use App\Models\User;

class CrmEmailMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('crm_email.view')
            || $user->hasPermission('customers.view');
    }

    public function view(User $user, CrmEmailMessage $message): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('customers.update')
            || $user->hasPermission('customers.create');
    }
}
