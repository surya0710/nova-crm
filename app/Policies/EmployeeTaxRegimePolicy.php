<?php

namespace App\Policies;

use App\Models\EmployeeTaxRegime;
use App\Models\User;

class EmployeeTaxRegimePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tax.view');
    }

    public function view(User $user, EmployeeTaxRegime $regime): bool
    {
        return $user->hasPermission('tax.view', $regime->organization);
    }

    public function select(User $user): bool
    {
        return $user->hasPermission('tax.manage');
    }
}
