<?php

namespace App\Policies;

use App\Models\TaxDeclaration;
use App\Models\User;

class TaxDeclarationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tax.view')
            || $user->hasPermission('tax.manage')
            || $user->hasPermission('tax.verify');
    }

    public function view(User $user, TaxDeclaration $declaration): bool
    {
        return $user->hasPermission('tax.view', $declaration->organization)
            || $user->hasPermission('tax.manage', $declaration->organization)
            || $user->hasPermission('tax.verify', $declaration->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tax.manage');
    }

    public function update(User $user, TaxDeclaration $declaration): bool
    {
        return $user->hasPermission('tax.manage', $declaration->organization);
    }

    public function submit(User $user, TaxDeclaration $declaration): bool
    {
        return $user->hasPermission('tax.manage', $declaration->organization);
    }

    public function verify(User $user, TaxDeclaration $declaration): bool
    {
        return $user->hasPermission('tax.verify', $declaration->organization);
    }

    public function reject(User $user, TaxDeclaration $declaration): bool
    {
        return $user->hasPermission('tax.verify', $declaration->organization);
    }
}
