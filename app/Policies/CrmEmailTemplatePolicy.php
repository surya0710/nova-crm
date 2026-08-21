<?php

namespace App\Policies;

use App\Models\CrmEmailTemplate;
use App\Models\User;

class CrmEmailTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('email_templates.view')
            || $user->hasPermission('email_templates.manage');
    }

    public function view(User $user, CrmEmailTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('email_templates.manage');
    }

    public function update(User $user, CrmEmailTemplate $template): bool
    {
        return $user->hasPermission('email_templates.manage');
    }

    public function delete(User $user, CrmEmailTemplate $template): bool
    {
        return $user->hasPermission('email_templates.manage');
    }
}
