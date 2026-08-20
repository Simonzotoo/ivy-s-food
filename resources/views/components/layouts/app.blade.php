<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
        <aside class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full bg-ivy-navy text-white transition lg:static lg:translate-x-0"
               :class="{ 'translate-x-0': sidebarOpen }">
            <div class="flex h-full flex-col">
                <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
                    <img src="{{ asset('images/ivys-logo.png') }}" alt="Ivy's Food" class="h-14 w-14 shrink-0 object-contain">
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold">Ivy's Food</p>
                        <p class="truncate text-xs text-slate-300">Management System</p>
                    </div>
                </div>
                <nav class="flex-1 space-y-1 px-3 py-4">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'nav-link-active' : '' }}">Setup</a>
                    @can('users.view')
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">Users</a>
                    @endcan
                    @can('roles.view')
                        <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'nav-link-active' : '' }}">Roles & Permissions</a>
                    @endcan
                    @can('catalogue.view')
                        <a href="{{ route('catalogue.index') }}" class="nav-link {{ request()->routeIs('catalogue.*') ? 'nav-link-active' : '' }}">Product Catalogue</a>
                    @endcan
                    @can('materials.view')
                        <a href="{{ route('materials.index') }}" class="nav-link {{ request()->routeIs('materials.*') ? 'nav-link-active' : '' }}">Materials</a>
                    @endcan
                    @can('suppliers.view')
                        <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'nav-link-active' : '' }}">Suppliers</a>
                    @endcan
                    @can('purchases.view')
                        <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'nav-link-active' : '' }}">Purchases</a>
                    @endcan
                    @can('recipes.view')
                        <a href="{{ route('recipes.index') }}" class="nav-link {{ request()->routeIs('recipes.*') ? 'nav-link-active' : '' }}">Recipes</a>
                    @endcan
                    @can('production.view')
                        <a href="{{ route('production.batches') }}" class="nav-link {{ request()->routeIs('production.*') ? 'nav-link-active' : '' }}">Production</a>
                    @endcan
                    @can('inventory.view')
                        <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'nav-link-active' : '' }}">Inventory</a>
                    @endcan
                    @can('customers.view')
                        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'nav-link-active' : '' }}">Customers</a>
                    @endcan
                    @can('sales.view')
                        <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'nav-link-active' : '' }}">Sales</a>
                    @endcan
                    @can('credit.view')
                        <a href="{{ route('credit.invoices') }}" class="nav-link {{ request()->routeIs('credit.*') ? 'nav-link-active' : '' }}">Credit</a>
                    @endcan
                    @can('expenses.view')
                        <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'nav-link-active' : '' }}">Expenses</a>
                    @endcan
                    @can('company.view')
                        <a href="{{ route('company.edit') }}" class="nav-link {{ request()->routeIs('company.*') ? 'nav-link-active' : '' }}">Company Settings</a>
                    @endcan
                    @can('audit.view')
                        <a href="{{ route('audit.index') }}" class="nav-link {{ request()->routeIs('audit.*') ? 'nav-link-active' : '' }}">Audit Logs</a>
                    @endcan
                </nav>
                <div class="border-t border-white/10 p-4">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-300">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </aside>

        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

        <section class="min-w-0">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" class="btn-secondary px-3 lg:hidden" @click="sidebarOpen = true" aria-label="Open navigation">Menu</button>
                        <div>
                            <h1 class="text-lg font-bold text-ivy-navy">{{ $title ?? 'System Setup' }}</h1>
                            <p class="hidden text-xs text-slate-500 sm:block">Ivy's Food operations</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('profile.edit') }}" class="btn-secondary">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn-primary" type="submit">Sign out</button>
                        </form>
                    </div>
                </div>
            </header>
            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif
                {{ $slot }}
            </main>
        </section>
    </div>
    @livewireScripts
</body>
</html>
