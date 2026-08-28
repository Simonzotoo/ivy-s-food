<?php

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * Business roles were consolidated to CEO / COO / Supervisor, but the test
     * suite still exercises fine-grained permission boundaries by the old
     * functional job-title labels. Each label here maps to the permission set
     * that job function needs, independent of whatever roles actually exist
     * in the Roles & Permissions screen today.
     *
     * @var array<string, list<string>>
     */
    private const LEGACY_ROLE_PERMISSIONS = [
        'Manager' => [
            'users.view', 'company.view', 'audit.view', 'roles.view', 'catalogue.view', 'catalogue.export',
            'materials.view', 'suppliers.view', 'purchases.view', 'material_stock.view', 'recipes.view',
            'recipes.approve', 'production.view', 'inventory.view', 'inventory.approve_stocktake', 'customers.view',
            'customers.manage', 'customers.manage_credit', 'customers.communications', 'sales.view', 'sales.discount',
            'sales.return', 'sales.reverse', 'sales.reprint', 'credit.view', 'credit.payments', 'credit.reminders',
            'credit.reports', 'expenses.view', 'expenses.create', 'expenses.approve', 'expenses.manage_categories',
            'expenses.reports', 'broadcasts.create', 'dashboard.view', 'reports.view', 'reports.export',
            'imports.view', 'imports.history', 'imports.templates', 'imports.upload', 'imports.validate',
            'imports.commit_customers', 'imports.commit_suppliers', 'imports.commit_catalogue',
            'imports.commit_opening_stock', 'imports.rollback',
        ],
        'Cashier / Sales Officer' => [
            'dashboard.view', 'catalogue.view', 'customers.view', 'customers.manage', 'sales.view', 'sales.create',
            'sales.reprint',
        ],
        'Inventory Officer' => [
            'dashboard.view', 'catalogue.view', 'catalogue.create', 'catalogue.edit', 'catalogue.activate',
            'catalogue.export', 'materials.view', 'materials.manage', 'suppliers.view', 'purchases.view',
            'purchases.receive', 'purchases.return', 'material_stock.view', 'recipes.view', 'production.view',
            'inventory.view', 'inventory.adjust', 'inventory.stocktake', 'inventory.post_production', 'imports.view',
            'imports.history', 'imports.templates', 'imports.upload', 'imports.validate', 'imports.commit_catalogue',
            'imports.commit_opening_stock',
        ],
        'Production Officer' => [
            'dashboard.view', 'catalogue.view', 'materials.view', 'material_stock.view', 'recipes.view',
            'recipes.manage', 'production.view', 'production.plan', 'production.start', 'production.complete',
            'inventory.view',
        ],
        'Purchasing Officer' => [
            'dashboard.view', 'catalogue.view', 'materials.view', 'materials.manage', 'suppliers.view',
            'suppliers.manage', 'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.receive',
            'purchases.return', 'material_stock.view', 'imports.view', 'imports.templates', 'imports.upload',
            'imports.validate', 'imports.commit_suppliers',
        ],
        'Accounts Officer' => [
            'customers.view', 'customers.manage_credit', 'sales.view', 'sales.reprint', 'credit.view',
            'credit.payments', 'credit.reminders', 'credit.reports', 'expenses.view', 'expenses.create',
            'expenses.approve', 'expenses.manage_categories', 'expenses.reports', 'dashboard.view', 'reports.view',
            'reports.export', 'imports.view', 'imports.history', 'imports.templates', 'imports.upload',
            'imports.validate', 'imports.commit_customers', 'imports.commit_opening_stock',
        ],
        'Marketing Officer' => [
            'dashboard.view', 'catalogue.view', 'catalogue.export', 'customers.view', 'customers.communications',
            'broadcasts.create',
        ],
        'Auditor' => [
            'audit.view', 'company.view', 'materials.view', 'suppliers.view', 'purchases.view', 'material_stock.view',
            'recipes.view', 'production.view', 'inventory.view', 'customers.view', 'sales.view', 'credit.view',
            'credit.reports', 'expenses.view', 'expenses.reports', 'dashboard.view', 'reports.view', 'reports.export',
            'imports.view', 'imports.history',
        ],
    ];

    /**
     * Build a user carrying exactly the permission set the given legacy job
     * title used to have. "Owner / Super Admin" and "CEO" both resolve to the
     * real, unrestricted CEO role.
     */
    protected function userWithRole(string $roleName): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password!234'),
            'status' => 'active',
            'password_change_required' => false,
        ]);

        if (in_array($roleName, ['Owner / Super Admin', 'CEO'], true)) {
            $user->roles()->attach(Role::query()->where('is_owner', true)->firstOrFail());

            return $user->fresh('roles.permissions');
        }

        $slug = Str::slug($roleName).'-'.Str::random(8);
        $role = Role::query()->create([
            'name' => $roleName.' ('.$slug.')',
            'slug' => $slug,
            'is_system' => false,
            'is_owner' => false,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', self::LEGACY_ROLE_PERMISSIONS[$roleName] ?? [])
            ->pluck('id');

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user->fresh('roles.permissions');
    }
}
