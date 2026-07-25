<?php

namespace App\Policies;

use App\Models\OfferLetter;
use App\Models\User;

class OfferLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.view');
    }

    public function view(User $user, OfferLetter $offer): bool
    {
        return $user->hasPermission('recruitment.offer.view', $offer->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.create');
    }

    public function update(User $user, OfferLetter $offer): bool
    {
        return $user->hasPermission('recruitment.offer.edit', $offer->organization);
    }

    public function delete(User $user, OfferLetter $offer): bool
    {
        return $user->hasPermission('recruitment.offer.delete', $offer->organization);
    }

    public function approve(User $user, OfferLetter $offer): bool
    {
        return $user->hasPermission('recruitment.offer.approve', $offer->organization);
    }
}
