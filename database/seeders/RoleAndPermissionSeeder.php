<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = [];

        foreach (Permissions::ALL as $slug => [$name, $group]) {
            $permission = Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'group' => $group]
            );

            $permissionIds[$slug] = $permission->id;
        }

        $roles = [
            'Owner / Super Admin' => ['is_owner' => true, 'description' => 'Unrestricted system owner access.'],
            'Manager' => ['permissions' => ['users.view', 'company.view', 'audit.view', 'roles.view']],
            'Cashier / Sales Officer' => ['permissions' => []],
            'Inventory Officer' => ['permissions' => []],
            'Production Officer' => ['permissions' => []],
            'Purchasing Officer' => ['permissions' => []],
            'Accounts Officer' => ['permissions' => []],
            'Marketing Officer' => ['permissions' => []],
            'Auditor' => ['permissions' => ['audit.view', 'company.view']],
        ];

        foreach ($roles as $name => $settings) {
            $role = Role::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $settings['description'] ?? null,
                    'is_system' => true,
                    'is_owner' => (bool) ($settings['is_owner'] ?? false),
                ]
            );

            if ($role->is_owner) {
                $role->permissions()->sync(array_values($permissionIds));

                continue;
            }

            $role->permissions()->sync(
                collect($settings['permissions'] ?? [])
                    ->map(fn (string $slug) => $permissionIds[$slug])
                    ->all()
            );
        }
    }
}
