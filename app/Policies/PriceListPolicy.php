<?php

namespace App\Policies;

use App\Models\PriceList;
use App\Models\User;

class PriceListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('price_lists.view');
    }

    public function view(User $user, PriceList $priceList): bool
    {
        return $user->hasPermission('price_lists.view', $priceList->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('price_lists.create');
    }

    public function update(User $user, PriceList $priceList): bool
    {
        return $user->hasPermission('price_lists.update', $priceList->organization);
    }

    public function delete(User $user, PriceList $priceList): bool
    {
        return $user->hasPermission('price_lists.delete', $priceList->organization)
            && ! $priceList->is_default;
    }
}
