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
            'Manager' => ['permissions' => ['users.view', 'company.view', 'audit.view', 'roles.view', 'catalogue.view', 'catalogue.export', 'materials.view', 'suppliers.view', 'purchases.view', 'material_stock.view', 'recipes.view', 'recipes.approve', 'production.view', 'inventory.view', 'inventory.approve_stocktake', 'customers.view', 'customers.manage', 'customers.manage_credit', 'customers.communications', 'sales.view', 'sales.discount', 'sales.return', 'sales.reverse', 'sales.reprint', 'credit.view', 'credit.payments', 'credit.reminders', 'credit.reports']],
            'Cashier / Sales Officer' => ['permissions' => ['catalogue.view', 'customers.view', 'customers.manage', 'sales.view', 'sales.create', 'sales.reprint']],
            'Inventory Officer' => ['permissions' => ['catalogue.view', 'catalogue.create', 'catalogue.edit', 'catalogue.activate', 'catalogue.export', 'materials.view', 'materials.manage', 'suppliers.view', 'purchases.view', 'purchases.receive', 'purchases.return', 'material_stock.view', 'recipes.view', 'production.view', 'inventory.view', 'inventory.adjust', 'inventory.stocktake', 'inventory.post_production']],
            'Production Officer' => ['permissions' => ['catalogue.view', 'materials.view', 'material_stock.view', 'recipes.view', 'recipes.manage', 'production.view', 'production.plan', 'production.start', 'production.complete', 'inventory.view']],
            'Purchasing Officer' => ['permissions' => ['catalogue.view', 'materials.view', 'materials.manage', 'suppliers.view', 'suppliers.manage', 'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.receive', 'purchases.return', 'material_stock.view']],
            'Accounts Officer' => ['permissions' => ['customers.view', 'customers.manage_credit', 'sales.view', 'sales.reprint', 'credit.view', 'credit.payments', 'credit.reminders', 'credit.reports']],
            'Marketing Officer' => ['permissions' => ['catalogue.view', 'catalogue.export', 'customers.view', 'customers.communications']],
            'Auditor' => ['permissions' => ['audit.view', 'company.view', 'materials.view', 'suppliers.view', 'purchases.view', 'material_stock.view', 'recipes.view', 'production.view', 'inventory.view', 'customers.view', 'sales.view', 'credit.view', 'credit.reports']],
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
