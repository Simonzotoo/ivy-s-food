<?php

namespace App\Support;

final class Permissions
{
    public const ALL = [
        'users.view' => ['View users', 'Users'],
        'users.create' => ['Create users', 'Users'],
        'users.edit' => ['Edit users', 'Users'],
        'users.activate_suspend' => ['Activate or suspend users', 'Users'],
        'roles.assign' => ['Assign roles', 'Users'],
        'roles.view' => ['View roles and permissions', 'Roles'],
        'roles.manage' => ['Manage roles and permissions', 'Roles'],
        'company.view' => ['View company settings', 'Company'],
        'company.edit' => ['Edit company settings', 'Company'],
        'audit.view' => ['View audit logs', 'Audit'],
        'catalogue.view' => ['View product catalogue', 'Product Catalogue'],
        'catalogue.create' => ['Create product catalogue records', 'Product Catalogue'],
        'catalogue.edit' => ['Edit product catalogue records', 'Product Catalogue'],
        'catalogue.activate' => ['Activate or deactivate product variants', 'Product Catalogue'],
        'catalogue.export' => ['Export product catalogue', 'Product Catalogue'],
        'materials.view' => ['View materials', 'Materials & Purchases'],
        'materials.manage' => ['Manage materials', 'Materials & Purchases'],
        'suppliers.view' => ['View suppliers', 'Materials & Purchases'],
        'suppliers.manage' => ['Manage suppliers', 'Materials & Purchases'],
        'purchases.view' => ['View purchases', 'Materials & Purchases'],
        'purchases.create' => ['Create purchases', 'Materials & Purchases'],
        'purchases.edit' => ['Edit draft purchases', 'Materials & Purchases'],
        'purchases.receive' => ['Receive purchases', 'Materials & Purchases'],
        'purchases.return' => ['Return purchases', 'Materials & Purchases'],
        'material_stock.view' => ['View material stock movements', 'Materials & Purchases'],
        'recipes.view' => ['View recipes', 'Recipes & Production'],
        'recipes.manage' => ['Manage recipes', 'Recipes & Production'],
        'recipes.approve' => ['Approve recipes', 'Recipes & Production'],
        'production.view' => ['View production', 'Recipes & Production'],
        'production.plan' => ['Plan production', 'Recipes & Production'],
        'production.start' => ['Create production batches', 'Recipes & Production'],
        'production.complete' => ['Complete production', 'Recipes & Production'],
        'production.reverse' => ['Reverse production', 'Recipes & Production'],
        'inventory.view' => ['View inventory', 'Inventory'],
        'inventory.adjust' => ['Adjust stock', 'Inventory'],
        'inventory.stocktake' => ['Create stocktakes', 'Inventory'],
        'inventory.approve_stocktake' => ['Approve stocktake variances', 'Inventory'],
        'inventory.post_production' => ['Post production output to inventory', 'Inventory'],
    ];
}
