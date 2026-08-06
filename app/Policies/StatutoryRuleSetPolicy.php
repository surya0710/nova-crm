<?php

namespace App\Policies;

use App\Models\StatutoryRuleSet;
use App\Models\User;

class StatutoryRuleSetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.statutory.view')
            || $user->hasPermission('payroll.statutory.configuration');
    }

    public function view(User $user, StatutoryRuleSet $ruleSet): bool
    {
        return $user->hasPermission('payroll.statutory.view', $ruleSet->organization)
            || $user->hasPermission('payroll.statutory.configuration', $ruleSet->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.statutory.configuration');
    }

    public function update(User $user, StatutoryRuleSet $ruleSet): bool
    {
        return $user->hasPermission('payroll.statutory.configuration', $ruleSet->organization);
    }

    public function activate(User $user, StatutoryRuleSet $ruleSet): bool
    {
        return $this->update($user, $ruleSet);
    }
}
