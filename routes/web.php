<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductCatalogueController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
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
});
