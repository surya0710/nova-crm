<?php

namespace App\Policies;

use App\Models\TaxFinancialYear;
use App\Models\User;

class TaxFinancialYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tax.view')
            || $user->hasPermission('tax.manage');
    }

    public function view(User $user, TaxFinancialYear $financialYear): bool
    {
        return $user->hasPermission('tax.view', $financialYear->organization)
            || $user->hasPermission('tax.manage', $financialYear->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tax.manage');
    }

    public function update(User $user, TaxFinancialYear $financialYear): bool
    {
        return $user->hasPermission('tax.manage', $financialYear->organization);
    }

    public function activate(User $user, TaxFinancialYear $financialYear): bool
    {
        return $user->hasPermission('tax.manage', $financialYear->organization);
    }
}
