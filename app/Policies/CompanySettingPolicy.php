<?php

namespace App\Policies;

use App\Models\CompanySetting;
use App\Models\User;

class CompanySettingPolicy
{
    public function view(User $user, CompanySetting $settings): bool
    {
        return $user->hasPermissionTo('company.view');
    }

    public function update(User $user, CompanySetting $settings): bool
    {
        return $user->hasPermissionTo('company.edit');
    }
}
