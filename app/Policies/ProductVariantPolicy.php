<?php

namespace App\Policies;

use App\Models\ProductVariant;
use App\Models\User;

class ProductVariantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('catalogue.view');
    }

    public function view(User $user, ProductVariant $variant): bool
    {
        return $user->hasPermissionTo('catalogue.view');
    }

    public function update(User $user, ProductVariant $variant): bool
    {
        return $user->hasPermissionTo('catalogue.edit');
    }

    public function changeStatus(User $user, ProductVariant $variant): bool
    {
        return $user->hasPermissionTo('catalogue.activate');
    }
}
