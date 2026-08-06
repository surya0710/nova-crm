<?php

namespace App\Policies;

use App\Models\OfferTemplate;
use App\Models\User;

class OfferTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.view');
    }

    public function view(User $user, OfferTemplate $template): bool
    {
        return $user->hasPermission('recruitment.offer.view', $template->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('recruitment.offer.create');
    }

    public function update(User $user, OfferTemplate $template): bool
    {
        return $user->hasPermission('recruitment.offer.edit', $template->organization);
    }

    public function delete(User $user, OfferTemplate $template): bool
    {
        return $user->hasPermission('recruitment.offer.delete', $template->organization);
    }
}
