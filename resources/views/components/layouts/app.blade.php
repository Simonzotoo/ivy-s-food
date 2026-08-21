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
        <aside class="fixed inset-y-0 left-0 z-40 w-72 bg-ivy-navy text-white transition lg:static lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-full flex-col overflow-y-auto">
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
                    @can('sales.view')
                        <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'nav-link-active' : '' }}">Sales</a>
                    @endcan
                    @canany(['production.view', 'recipes.view'])
                        <details class="nav-group" {{ request()->routeIs('production.*') || request()->routeIs('recipes.*') ? 'open' : '' }}>
                            <summary>Production</summary>
                            <div class="mt-1 space-y-1">
                                @can('production.view')
                                    <a href="{{ route('production.batches') }}" class="nav-sublink {{ request()->routeIs('production.batches*') ? 'nav-link-active' : '' }}">Record Production</a>
                                    <a href="{{ route('production.plans') }}" class="nav-sublink {{ request()->routeIs('production.plans*') ? 'nav-link-active' : '' }}">Production Plans</a>
                                @endcan
                                @can('recipes.view')
                                    <a href="{{ route('recipes.index') }}" class="nav-sublink {{ request()->routeIs('recipes.*') ? 'nav-link-active' : '' }}">Recipes</a>
                                @endcan
                            </div>
                        </details>
                    @endcanany
                    @can('inventory.view')
                        <details class="nav-group" {{ request()->routeIs('inventory.*') ? 'open' : '' }}>
                            <summary>Inventory</summary>
                            <div class="mt-1 space-y-1">
                                <a href="{{ route('inventory.index') }}" class="nav-sublink {{ request()->routeIs('inventory.index') ? 'nav-link-active' : '' }}">Check Stock</a>
                                <a href="{{ route('inventory.movements') }}" class="nav-sublink {{ request()->routeIs('inventory.movements') ? 'nav-link-active' : '' }}">Stock History</a>
                                <a href="{{ route('inventory.finished-batches') }}" class="nav-sublink {{ request()->routeIs('inventory.finished-batches') ? 'nav-link-active' : '' }}">Finished Product Batches</a>
                                <a href="{{ route('inventory.stocktakes') }}" class="nav-sublink {{ request()->routeIs('inventory.stocktakes*') ? 'nav-link-active' : '' }}">Stocktakes</a>
                                @can('inventory.adjust')
                                    <a href="{{ route('inventory.adjustments') }}" class="nav-sublink {{ request()->routeIs('inventory.adjustments*') ? 'nav-link-active' : '' }}">Adjust Stock</a>
                                @endcan
                            </div>
                        </details>
                    @endcan
                    @canany(['purchases.view', 'materials.view', 'suppliers.view'])
                        <details class="nav-group" {{ request()->routeIs('purchases.*') || request()->routeIs('materials.*') || request()->routeIs('suppliers.*') ? 'open' : '' }}>
                            <summary>Purchases</summary>
                            <div class="mt-1 space-y-1">
                                @can('purchases.view')
                                    <a href="{{ route('purchases.index') }}" class="nav-sublink {{ request()->routeIs('purchases.*') ? 'nav-link-active' : '' }}">Purchases</a>
                                @endcan
                                @can('materials.view')
                                    <a href="{{ route('materials.index') }}" class="nav-sublink {{ request()->routeIs('materials.index') || request()->routeIs('materials.create') || request()->routeIs('materials.edit') ? 'nav-link-active' : '' }}">Materials</a>
                                    <a href="{{ route('materials.movements') }}" class="nav-sublink {{ request()->routeIs('materials.movements') ? 'nav-link-active' : '' }}">Materials Used</a>
                                @endcan
                                @can('suppliers.view')
                                    <a href="{{ route('suppliers.index') }}" class="nav-sublink {{ request()->routeIs('suppliers.*') ? 'nav-link-active' : '' }}">Suppliers</a>
                                @endcan
                            </div>
                        </details>
                    @endcanany
                    @can('customers.view')
                        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'nav-link-active' : '' }}">Customers</a>
                    @endcan
                    @can('credit.view')
                        <details class="nav-group" {{ request()->routeIs('credit.*') ? 'open' : '' }}>
                            <summary>Credit</summary>
                            <div class="mt-1 space-y-1">
                                <a href="{{ route('credit.invoices') }}" class="nav-sublink {{ request()->routeIs('credit.invoices*') ? 'nav-link-active' : '' }}">Customers Owing</a>
                                @can('credit.payments')
                                    <a href="{{ route('credit.payments') }}" class="nav-sublink {{ request()->routeIs('credit.payments') ? 'nav-link-active' : '' }}">Receive Payment</a>
                                @endcan
                                @can('credit.reports')
                                    <a href="{{ route('credit.aging') }}" class="nav-sublink {{ request()->routeIs('credit.aging') ? 'nav-link-active' : '' }}">Aging Report</a>
                                @endcan
                                @can('credit.reminders')
                                    <a href="{{ route('credit.reminders') }}" class="nav-sublink {{ request()->routeIs('credit.reminders') ? 'nav-link-active' : '' }}">Reminders</a>
                                @endcan
                            </div>
                        </details>
                    @endcan
                    @can('expenses.view')
                        <details class="nav-group" {{ request()->routeIs('expenses.*') ? 'open' : '' }}>
                            <summary>Expenses</summary>
                            <div class="mt-1 space-y-1">
                                <a href="{{ route('expenses.index') }}" class="nav-sublink {{ request()->routeIs('expenses.index') || request()->routeIs('expenses.create') || request()->routeIs('expenses.show') ? 'nav-link-active' : '' }}">Expenses</a>
                                @can('expenses.manage_categories')
                                    <a href="{{ route('expenses.categories') }}" class="nav-sublink {{ request()->routeIs('expenses.categories') ? 'nav-link-active' : '' }}">Categories</a>
                                @endcan
                                @can('expenses.reports')
                                    <a href="{{ route('expenses.report') }}" class="nav-sublink {{ request()->routeIs('expenses.report') ? 'nav-link-active' : '' }}">Expense Report</a>
                                @endcan
                            </div>
                        </details>
                    @endcan
                    @can('reports.view')
                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}">Reports</a>
                    @endcan
                    @canany(['broadcasts.view', 'broadcasts.create', 'broadcasts.manage_templates', 'broadcasts.manage_consent'])
                        <details class="nav-group" {{ request()->routeIs('broadcasts.*') ? 'open' : '' }}>
                            <summary>Communication</summary>
                            <div class="mt-1 space-y-1">
                                @can('broadcasts.view')
                                    <a href="{{ route('broadcasts.index') }}" class="nav-sublink {{ request()->routeIs('broadcasts.index') || request()->routeIs('broadcasts.show') ? 'nav-link-active' : '' }}">Customer Messages</a>
                                @endcan
                                @can('broadcasts.create')
                                    <a href="{{ route('broadcasts.create') }}" class="nav-sublink {{ request()->routeIs('broadcasts.create') ? 'nav-link-active' : '' }}">Send Message</a>
                                @endcan
                                @can('broadcasts.manage_templates')
                                    <a href="{{ route('broadcasts.templates') }}" class="nav-sublink {{ request()->routeIs('broadcasts.templates') ? 'nav-link-active' : '' }}">Message Templates</a>
                                @endcan
                                @can('broadcasts.manage_consent')
                                    <a href="{{ route('broadcasts.consent') }}" class="nav-sublink {{ request()->routeIs('broadcasts.consent') ? 'nav-link-active' : '' }}">Customer Consent</a>
                                @endcan
                            </div>
                        </details>
                    @endcanany
                    @can('imports.view')
                        <details class="nav-group" {{ request()->routeIs('imports.*') ? 'open' : '' }}>
                            <summary>Data Imports</summary>
                            <div class="mt-1 space-y-1">
                                <a href="{{ route('imports.index') }}" class="nav-sublink {{ request()->routeIs('imports.*') ? 'nav-link-active' : '' }}">Import Data</a>
                            </div>
                        </details>
                    @endcan
                    @canany(['users.view', 'roles.view', 'audit.view'])
                        <details class="nav-group" {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('audit.*') ? 'open' : '' }}>
                            <summary>Administration</summary>
                            <div class="mt-1 space-y-1">
                                @can('users.view')
                                    <a href="{{ route('users.index') }}" class="nav-sublink {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">Staff Accounts</a>
                                @endcan
                                @can('roles.view')
                                    <a href="{{ route('roles.index') }}" class="nav-sublink {{ request()->routeIs('roles.*') ? 'nav-link-active' : '' }}">Roles & Permissions</a>
                                @endcan
                                @can('audit.view')
                                    <a href="{{ route('audit.index') }}" class="nav-sublink {{ request()->routeIs('audit.*') ? 'nav-link-active' : '' }}">Audit History</a>
                                @endcan
                            </div>
                        </details>
                    @endcanany
                    @canany(['catalogue.view', 'company.view'])
                        <details class="nav-group" {{ request()->routeIs('catalogue.*') || request()->routeIs('company.*') ? 'open' : '' }}>
                            <summary>Settings</summary>
                            <div class="mt-1 space-y-1">
                                @can('catalogue.view')
                                    <a href="{{ route('catalogue.index') }}" class="nav-sublink {{ request()->routeIs('catalogue.*') ? 'nav-link-active' : '' }}">Products and Sizes</a>
                                @endcan
                                @can('company.view')
                                    <a href="{{ route('company.edit') }}" class="nav-sublink {{ request()->routeIs('company.*') ? 'nav-link-active' : '' }}">Company Details</a>
                                @endcan
                            </div>
                        </details>
                    @endcanany
                </nav>
                @php($profilePhotoUrl = auth()->user()->profilePhotoUrl())
                <div class="mx-3 mb-4 rounded-md border border-white/10 bg-white/[0.08] p-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        @if ($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="{{ auth()->user()->name }}" class="h-11 w-11 shrink-0 rounded-full object-cover ring-2 ring-white/20">
                        @else
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-ivy-navy ring-2 ring-white/20">
                                {{ auth()->user()->initials() }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-300">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="mt-3 inline-flex w-full items-center justify-center rounded-md border border-white/15 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/40">
                        View profile
                    </a>
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
