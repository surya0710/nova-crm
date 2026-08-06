<?php

namespace App\Policies;

use App\Models\TaxProof;
use App\Models\User;

class TaxProofPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tax.view')
            || $user->hasPermission('tax.verify');
    }

    public function view(User $user, TaxProof $proof): bool
    {
        return $user->hasPermission('tax.view', $proof->organization)
            || $user->hasPermission('tax.verify', $proof->organization);
    }

    public function upload(User $user): bool
    {
        return $user->hasPermission('tax.manage');
    }

    public function verify(User $user, TaxProof $proof): bool
    {
        return $user->hasPermission('tax.verify', $proof->organization);
    }

    public function reject(User $user, TaxProof $proof): bool
    {
        return $user->hasPermission('tax.verify', $proof->organization);
    }
}
