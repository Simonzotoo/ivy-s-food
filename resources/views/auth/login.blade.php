<x-layouts.auth>
    <h2 class="text-2xl font-bold text-ivy-navy">Sign in</h2>
    <p class="mt-2 text-sm text-slate-600">Use your staff account to continue.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="form-input">
        </div>
        <div>
            <label for="password" class="form-label">Password</label>
            <x-password-toggle id="password" name="password" autocomplete="current-password" required />
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red">
            Remember this browser
        </label>
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-ivy-navy hover:text-ivy-red">Forgot password?</a>
            <button class="btn-primary" type="submit">Sign in</button>
        </div>
    </form>
</x-layouts.auth>
