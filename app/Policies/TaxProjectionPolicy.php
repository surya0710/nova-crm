<?php

namespace App\Policies;

use App\Models\TaxProjection;
use App\Models\User;

class TaxProjectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tax.view')
            || $user->hasPermission('tax.calculate');
    }

    public function view(User $user, TaxProjection $projection): bool
    {
        return $user->hasPermission('tax.view', $projection->organization)
            || $user->hasPermission('tax.calculate', $projection->organization);
    }

    public function calculate(User $user): bool
    {
        return $user->hasPermission('tax.calculate')
            || $user->hasPermission('tax.manage');
    }
}
