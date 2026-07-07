<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.view', $quotation->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.update', $quotation->organization)
            && $quotation->isEditable();
    }

    public function changeStatus(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.update', $quotation->organization);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.delete', $quotation->organization)
            && $quotation->isDeletable();
    }

    public function convert(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('invoices.create', $quotation->organization)
            && $user->hasPermission('quotations.view', $quotation->organization);
    }
}
