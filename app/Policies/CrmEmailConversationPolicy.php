<?php

namespace App\Policies;

use App\Models\CrmEmailConversation;
use App\Models\User;

class CrmEmailConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('crm_email.view')
            || $user->hasPermission('customers.view');
    }

    public function view(User $user, CrmEmailConversation $conversation): bool
    {
        return $this->viewAny($user);
    }
}
