<?php

namespace App\Policies;

use App\Models\PayrollBankExport;
use App\Models\User;

class PayrollBankExportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.finance.view');
    }

    public function view(User $user, PayrollBankExport $export): bool
    {
        return $user->hasPermission('payroll.finance.view', $export->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payroll.bank.export');
    }
}
