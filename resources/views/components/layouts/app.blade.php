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
               :class="{ '!translate-x-0': sidebarOpen }">
            <div class="flex h-full flex-col">
                <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
                    <div class="flex h-20 w-28 shrink-0 items-center justify-center rounded-md bg-white p-1.5 shadow-sm ring-1 ring-white/20">
                        <img src="{{ asset('images/ivys-logo.png') }}" alt="Ivy's Food" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold">Ivy's Food</p>
                        <p class="truncate text-xs text-slate-300">Management System</p>
                    </div>
                </div>
                <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
                    @can('dashboard.view')
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'nav-link-active' : '' }}">Home</a>
                    @endcan
                    @canany(['sales.view', 'sales.create', 'production.view', 'inventory.view', 'purchases.view', 'customers.view', 'credit.view', 'expenses.view', 'reports.view'])
                        <div>
                            <p class="nav-section-label">Daily work</p>
                            <div class="mt-1 space-y-1">
                                @can('sales.view')
                                    <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'nav-link-active' : '' }}">Sales</a>
                                @endcan
                                @can('production.view')
                                    <a href="{{ route('production.batches') }}" class="nav-link {{ request()->routeIs('production.*') || request()->routeIs('recipes.*') ? 'nav-link-active' : '' }}">Production</a>
                                @endcan
                                @can('inventory.view')
                                    <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'nav-link-active' : '' }}">Inventory</a>
                                @endcan
                                @can('purchases.view')
                                    <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') || request()->routeIs('materials.*') || request()->routeIs('suppliers.*') ? 'nav-link-active' : '' }}">Purchases</a>
                                @endcan
                                @can('customers.view')
                                    <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'nav-link-active' : '' }}">Customers</a>
                                @endcan
                                @can('credit.view')
                                    <a href="{{ route('credit.invoices') }}" class="nav-link {{ request()->routeIs('credit.*') ? 'nav-link-active' : '' }}">Credit</a>
                                @endcan
                                @can('expenses.view')
                                    <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'nav-link-active' : '' }}">Expenses</a>
                                @endcan
                                @can('reports.view')
                                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}">Reports</a>
                                @endcan
                            </div>
                        </div>
                    @endcanany
                    @canany(['broadcasts.view', 'broadcasts.create', 'broadcasts.manage_templates', 'broadcasts.manage_consent'])
                        <details class="nav-group" {{ request()->routeIs('broadcasts.*') ? 'open' : '' }}>
                            <summary>Communication</summary>
                            <div class="mt-1 space-y-1">
                                @can('broadcasts.view')
                                    <a href="{{ route('broadcasts.index') }}" class="nav-sublink {{ request()->routeIs('broadcasts.index', 'broadcasts.show') ? 'nav-link-active' : '' }}">Customer messages</a>
                                @endcan
                                @can('broadcasts.create')
                                    <a href="{{ route('broadcasts.create') }}" class="nav-sublink {{ request()->routeIs('broadcasts.create') ? 'nav-link-active' : '' }}">Send message</a>
                                @endcan
                                @can('broadcasts.manage_templates')
                                    <a href="{{ route('broadcasts.templates') }}" class="nav-sublink {{ request()->routeIs('broadcasts.templates') ? 'nav-link-active' : '' }}">Message templates</a>
                                @endcan
                                @can('broadcasts.manage_consent')
                                    <a href="{{ route('broadcasts.consent') }}" class="nav-sublink {{ request()->routeIs('broadcasts.consent') ? 'nav-link-active' : '' }}">Customer consent</a>
                                @endcan
                            </div>
                        </details>
                    @endcanany
                    @canany(['users.view', 'roles.view', 'audit.view', 'imports.view'])
                        <details class="nav-group" {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('audit.*') || request()->routeIs('imports.*') ? 'open' : '' }}>
                            <summary>Administration</summary>
                            <div class="mt-1 space-y-1">
                                @can('users.view')
                                    <a href="{{ route('users.index') }}" class="nav-sublink {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">Staff accounts</a>
                                @endcan
                                @can('roles.view')
                                    <a href="{{ route('roles.index') }}" class="nav-sublink {{ request()->routeIs('roles.*') ? 'nav-link-active' : '' }}">Access rules</a>
                                @endcan
                                @can('imports.view')
                                    <a href="{{ route('imports.index') }}" class="nav-sublink {{ request()->routeIs('imports.*') ? 'nav-link-active' : '' }}">Import data</a>
                                @endcan
                                @can('audit.view')
                                    <a href="{{ route('audit.index') }}" class="nav-sublink {{ request()->routeIs('audit.*') ? 'nav-link-active' : '' }}">Audit history</a>
                                @endcan
                            </div>
                        </details>
                    @endcanany
                    @canany(['catalogue.view', 'materials.view', 'suppliers.view', 'company.view'])
                        <details class="nav-group" {{ request()->routeIs('catalogue.*') || request()->routeIs('materials.*') || request()->routeIs('suppliers.*') || request()->routeIs('company.*') ? 'open' : '' }}>
                            <summary>Settings</summary>
                            <div class="mt-1 space-y-1">
                                @can('catalogue.view')
                                    <a href="{{ route('catalogue.index') }}" class="nav-sublink {{ request()->routeIs('catalogue.*') ? 'nav-link-active' : '' }}">Products and sizes</a>
                                @endcan
                                @can('materials.view')
                                    <a href="{{ route('materials.index') }}" class="nav-sublink {{ request()->routeIs('materials.*') ? 'nav-link-active' : '' }}">Materials list</a>
                                @endcan
                                @can('suppliers.view')
                                    <a href="{{ route('suppliers.index') }}" class="nav-sublink {{ request()->routeIs('suppliers.*') ? 'nav-link-active' : '' }}">Suppliers list</a>
                                @endcan
                                @can('company.view')
                                    <a href="{{ route('company.edit') }}" class="nav-sublink {{ request()->routeIs('company.*') ? 'nav-link-active' : '' }}">Company details</a>
                                @endcan
                            </div>
                        </details>
                    @endcanany
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
