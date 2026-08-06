<?php

namespace App\Policies;

use App\Models\OfferNegotiation;
use App\Models\User;

class OfferNegotiationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.view');
    }

    public function view(User $user, OfferNegotiation $negotiation): bool
    {
        return $user->hasPermission('recruitment.offer.view', $negotiation->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.edit');
    }

    public function update(User $user, OfferNegotiation $negotiation): bool
    {
        return $user->hasPermission('recruitment.offer.edit', $negotiation->organization);
    }
}
