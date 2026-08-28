<x-layouts.auth>
    <h2 class="text-2xl font-bold text-ivy-navy">Reset password</h2>
    <p class="mt-2 text-sm text-slate-600">Enter your account email and a secure reset link will be sent if the account exists.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="form-input">
        </div>
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="text-sm font-semibold text-ivy-navy hover:text-ivy-red">Back to sign in</a>
            <button class="btn-primary" type="submit">Send reset link</button>
        </div>
    </form>
</x-layouts.auth>
