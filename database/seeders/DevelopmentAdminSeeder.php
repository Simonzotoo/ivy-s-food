<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('IVY_DEV_ADMIN_EMAIL');
        $password = env('IVY_DEV_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => env('IVY_DEV_ADMIN_NAME', 'Development Owner'),
                'phone' => env('IVY_DEV_ADMIN_PHONE'),
                'password' => Hash::make($password),
                'status' => 'active',
                'password_change_required' => false,
            ]
        );

        $ownerRole = Role::query()->where('is_owner', true)->first();
        if ($ownerRole && ! $user->roles()->whereKey($ownerRole->id)->exists()) {
            $user->roles()->attach($ownerRole->id);
        }
    }
}
