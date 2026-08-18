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
<body class="min-h-screen bg-white">
    <main class="grid min-h-screen lg:grid-cols-[1fr_520px]">
        <section class="hidden bg-ivy-navy p-10 text-white lg:flex lg:flex-col lg:justify-between">
            <img src="{{ asset('images/ivys-logo.png') }}" alt="Ivy's Food" class="h-28 w-28 object-contain">
            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-red-200">Ivy's Food</p>
                <h1 class="mt-4 text-4xl font-bold leading-tight">Production and business controls start with secure access.</h1>
                <p class="mt-5 text-base leading-7 text-slate-200">Module 1 establishes staff access, roles, company settings and traceable audit history.</p>
            </div>
            <p class="text-sm text-slate-300">Currency: GHS · Time zone: Africa/Accra</p>
        </section>
        <section class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-4 lg:hidden">
                    <img src="{{ asset('images/ivys-logo.png') }}" alt="Ivy's Food" class="h-16 w-16 object-contain">
                    <div>
                        <p class="text-lg font-bold text-ivy-navy">Ivy's Food</p>
                        <p class="text-sm text-slate-600">Management System</p>
                    </div>
                </div>
                {{ $slot }}
            </div>
        </section>
    </main>
    @livewireScripts
</body>
</html>
