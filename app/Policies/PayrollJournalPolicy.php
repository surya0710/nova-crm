<?php

namespace App\Policies;

use App\Models\PayrollJournal;
use App\Models\User;

class PayrollJournalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.finance.view');
    }

    public function view(User $user, PayrollJournal $journal): bool
    {
        return $user->hasPermission('payroll.finance.view', $journal->organization);
    }
}
