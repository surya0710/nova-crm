<?php

namespace App\Policies;

use App\Models\HiringDecision;
use App\Models\User;

class HiringDecisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.view');
    }

    public function view(User $user, HiringDecision $decision): bool
    {
        return $user->hasPermission('recruitment.offer.view', $decision->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.create');
    }

    public function update(User $user, HiringDecision $decision): bool
    {
        return $user->hasPermission('recruitment.offer.edit', $decision->organization);
    }
}
