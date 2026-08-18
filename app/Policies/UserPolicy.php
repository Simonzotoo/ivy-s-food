<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->hasPermissionTo('users.edit') && (! $managedUser->isOwner() || $user->isOwner());
    }

    public function changeStatus(User $user, User $managedUser): bool
    {
        return $user->hasPermissionTo('users.activate_suspend') && (! $managedUser->isOwner() || $user->isOwner());
    }
}
