<?php

namespace App\Policies;

use App\Models\Form16Record;
use App\Models\User;

class Form16RecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tax.view')
            || $user->hasPermission('form16.generate');
    }

    public function view(User $user, Form16Record $record): bool
    {
        return $user->hasPermission('tax.view', $record->organization)
            || $user->hasPermission('form16.generate', $record->organization);
    }

    public function generate(User $user): bool
    {
        return $user->hasPermission('form16.generate')
            || $user->hasPermission('tax.manage');
    }
}
