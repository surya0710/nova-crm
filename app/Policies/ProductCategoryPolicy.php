<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.view');
    }

    public function view(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('products.view', $productCategory->organization);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('products.create') || $user->hasPermission('products.manage');
    }

    public function update(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('products.update', $productCategory->organization)
            || $user->hasPermission('products.manage', $productCategory->organization);
    }

    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('products.delete', $productCategory->organization)
            || $user->hasPermission('products.manage', $productCategory->organization);
    }
}
