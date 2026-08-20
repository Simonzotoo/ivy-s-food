<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductCatalogueController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth', 'active', 'password.changed'])->group(function (): void {
    Route::get('/home', HomeController::class)->name('home');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->withoutMiddleware('password.changed');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit')->withoutMiddleware('password.changed');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password')->withoutMiddleware('password.changed');

    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('can:users.view');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware('can:users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('can:users.create');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:users.edit');
    Route::patch('/users/{user}/status', [UserController::class, 'status'])->name('users.status')->middleware('can:users.activate_suspend');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index')->middleware('can:roles.view');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update')->middleware('can:roles.manage');

    Route::get('/company-settings', [CompanySettingsController::class, 'edit'])->name('company.edit')->middleware('can:company.view');
    Route::put('/company-settings', [CompanySettingsController::class, 'update'])->name('company.update')->middleware('can:company.edit');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index')->middleware('can:audit.view');

    Route::get('/catalogue', [ProductCatalogueController::class, 'index'])->name('catalogue.index')->middleware('can:catalogue.view');
    Route::get('/catalogue/export', [ProductCatalogueController::class, 'export'])->name('catalogue.export')->middleware('can:catalogue.export');
    Route::get('/catalogue/variants/{variant}/edit', [ProductCatalogueController::class, 'edit'])->name('catalogue.variants.edit')->middleware('can:catalogue.edit');
    Route::put('/catalogue/variants/{variant}', [ProductCatalogueController::class, 'update'])->name('catalogue.variants.update')->middleware('can:catalogue.edit');
    Route::patch('/catalogue/variants/{variant}/status', [ProductCatalogueController::class, 'status'])->name('catalogue.variants.status')->middleware('can:catalogue.activate');

    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index')->middleware('can:materials.view');
    Route::get('/materials/create', [MaterialController::class, 'create'])->name('materials.create')->middleware('can:materials.manage');
    Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store')->middleware('can:materials.manage');
    Route::get('/materials/{material}/edit', [MaterialController::class, 'edit'])->name('materials.edit')->middleware('can:materials.manage');
    Route::put('/materials/{material}', [MaterialController::class, 'update'])->name('materials.update')->middleware('can:materials.manage');
    Route::get('/material-stock-movements', [PurchaseController::class, 'movements'])->name('materials.movements')->middleware('can:material_stock.view');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index')->middleware('can:suppliers.view');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create')->middleware('can:suppliers.manage');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store')->middleware('can:suppliers.manage');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit')->middleware('can:suppliers.manage');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')->middleware('can:suppliers.manage');

    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index')->middleware('can:purchases.view');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create')->middleware('can:purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store')->middleware('can:purchases.create');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show')->middleware('can:purchases.view');
    Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive')->middleware('can:purchases.receive');
    Route::post('/material-batches/{batch}/return', [PurchaseController::class, 'returnBatch'])->name('material-batches.return')->middleware('can:purchases.return');

    Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index')->middleware('can:recipes.view');
    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create')->middleware('can:recipes.manage');
    Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store')->middleware('can:recipes.manage');
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show')->middleware('can:recipes.view');
    Route::post('/recipes/{recipe}/approve', [RecipeController::class, 'approve'])->name('recipes.approve')->middleware('can:recipes.approve');

    Route::get('/production/plans', [ProductionController::class, 'plans'])->name('production.plans')->middleware('can:production.view');
    Route::get('/production/plans/create', [ProductionController::class, 'createPlan'])->name('production.plans.create')->middleware('can:production.plan');
    Route::post('/production/plans', [ProductionController::class, 'storePlan'])->name('production.plans.store')->middleware('can:production.plan');
    Route::get('/production/batches', [ProductionController::class, 'batches'])->name('production.batches')->middleware('can:production.view');
    Route::get('/production/batches/create', [ProductionController::class, 'createBatch'])->name('production.batches.create')->middleware('can:production.start');
    Route::post('/production/batches', [ProductionController::class, 'storeBatch'])->name('production.batches.store')->middleware('can:production.start');
    Route::get('/production/batches/{batch}', [ProductionController::class, 'showBatch'])->name('production.batches.show')->middleware('can:production.view');
    Route::post('/production/batches/{batch}/complete', [ProductionController::class, 'completeBatch'])->name('production.batches.complete')->middleware('can:production.complete');
    Route::post('/production/batches/{batch}/reverse', [ProductionController::class, 'reverseBatch'])->name('production.batches.reverse')->middleware('can:production.reverse');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('can:inventory.view');
    Route::get('/inventory/finished-batches', [InventoryController::class, 'finishedBatches'])->name('inventory.finished-batches')->middleware('can:inventory.view');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements')->middleware('can:inventory.view');
    Route::get('/inventory/adjustments', [InventoryController::class, 'adjustments'])->name('inventory.adjustments')->middleware('can:inventory.adjust');
    Route::post('/inventory/adjustments', [InventoryController::class, 'storeAdjustment'])->name('inventory.adjustments.store')->middleware('can:inventory.adjust');
    Route::post('/inventory/production-outputs/{output}/post', [InventoryController::class, 'postProductionOutput'])->name('inventory.production-outputs.post')->middleware('can:inventory.post_production');
    Route::get('/inventory/stocktakes', [InventoryController::class, 'stocktakes'])->name('inventory.stocktakes')->middleware('can:inventory.view');
    Route::get('/inventory/stocktakes/create', [InventoryController::class, 'createStocktake'])->name('inventory.stocktakes.create')->middleware('can:inventory.stocktake');
    Route::post('/inventory/stocktakes', [InventoryController::class, 'storeStocktake'])->name('inventory.stocktakes.store')->middleware('can:inventory.stocktake');
    Route::post('/inventory/stocktakes/{stocktake}/approve', [InventoryController::class, 'approveStocktake'])->name('inventory.stocktakes.approve')->middleware('can:inventory.approve_stocktake');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index')->middleware('can:customers.view');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create')->middleware('can:customers.manage');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store')->middleware('can:customers.manage');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show')->middleware('can:customers.view');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit')->middleware('can:customers.manage');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update')->middleware('can:customers.manage');
    Route::post('/customers/{customer}/communications', [CustomerController::class, 'logCommunication'])->name('customers.communications.store')->middleware('can:customers.communications');

    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index')->middleware('can:sales.view');
    Route::get('/sales/pos', [SalesController::class, 'pos'])->name('sales.pos')->middleware('can:sales.create');
    Route::post('/sales', [SalesController::class, 'store'])->name('sales.store')->middleware('can:sales.create');
    Route::get('/sales/{sale}', [SalesController::class, 'show'])->name('sales.show')->middleware('can:sales.view');
    Route::get('/sales/{sale}/receipt', [SalesController::class, 'receipt'])->name('sales.receipt')->middleware('can:sales.reprint');
    Route::get('/sales/{sale}/{document}.pdf', [SalesController::class, 'pdf'])->name('sales.pdf')->middleware('can:sales.reprint');
    Route::post('/sales/{sale}/reverse', [SalesController::class, 'reverse'])->name('sales.reverse')->middleware('can:sales.reverse');
    Route::post('/sales/{sale}/returns', [SalesController::class, 'returnItems'])->name('sales.returns.store')->middleware('can:sales.return');

    Route::get('/credit/invoices', [CreditController::class, 'invoices'])->name('credit.invoices')->middleware('can:credit.view');
    Route::get('/credit/invoices/{invoice}', [CreditController::class, 'show'])->name('credit.invoices.show')->middleware('can:credit.view');
    Route::post('/credit/invoices/{invoice}/reminders', [CreditController::class, 'sendReminder'])->name('credit.invoices.reminders')->middleware('can:credit.reminders');
    Route::get('/credit/payments', [CreditController::class, 'payments'])->name('credit.payments')->middleware('can:credit.payments');
    Route::post('/credit/payments', [CreditController::class, 'storePayment'])->name('credit.payments.store')->middleware('can:credit.payments');
    Route::get('/credit/payments/{payment}/receipt.pdf', [CreditController::class, 'paymentReceipt'])->name('credit.payments.receipt')->middleware('can:credit.view');
    Route::get('/credit/customers/{customer}/statement', [CreditController::class, 'statement'])->name('credit.statement')->middleware('can:credit.reports');
    Route::get('/credit/aging', [CreditController::class, 'aging'])->name('credit.aging')->middleware('can:credit.reports');
    Route::get('/credit/reminders', [CreditController::class, 'reminders'])->name('credit.reminders')->middleware('can:credit.reminders');
    Route::post('/credit/reminders/templates', [CreditController::class, 'storeTemplate'])->name('credit.reminders.templates.store')->middleware('can:credit.reminders');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware('can:expenses.view');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create')->middleware('can:expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware('can:expenses.create');
    Route::get('/expenses/categories', [ExpenseController::class, 'categories'])->name('expenses.categories')->middleware('can:expenses.manage_categories');
    Route::post('/expenses/categories', [ExpenseController::class, 'storeCategory'])->name('expenses.categories.store')->middleware('can:expenses.manage_categories');
    Route::get('/expenses/report', [ExpenseController::class, 'report'])->name('expenses.report')->middleware('can:expenses.reports');
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show')->middleware('can:expenses.view');
    Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve')->middleware('can:expenses.approve');
    Route::post('/expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject')->middleware('can:expenses.approve');
});
