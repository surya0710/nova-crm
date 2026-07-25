<?php

namespace App\Policies;

use App\Models\OfferApproval;
use App\Models\User;

class OfferApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.view');
    }

    public function view(User $user, OfferApproval $approval): bool
    {
        return $user->hasPermission('recruitment.offer.view', $approval->organization);
    }

    public function update(User $user, OfferApproval $approval): bool
    {
        return (int) $approval->approver_id === (int) $user->id
            || $user->hasPermission('recruitment.offer.approve', $approval->organization);
    }
}
