<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ivy's Food Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ivy-soft text-ivy-black">
    <main class="flex min-h-screen items-center justify-center px-4">
        <section class="max-w-lg rounded-md border border-slate-200 bg-white p-8 text-center shadow-sm">
            <img src="{{ asset('images/ivys-logo.png') }}" alt="Ivy's Food" class="mx-auto h-20 w-20 object-contain">
            <h1 class="mt-5 text-xl font-bold text-ivy-navy">Access denied</h1>
            <p class="mt-2 text-sm text-slate-600">Your account does not have permission to perform this action.</p>
        </section>
    </main>
</body>
</html>
