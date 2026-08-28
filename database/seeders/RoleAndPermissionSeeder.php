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
            'CEO' => ['is_owner' => true, 'description' => 'Unrestricted system owner access.'],
            'COO' => ['permissions' => []],
            'Supervisor' => ['permissions' => []],
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
