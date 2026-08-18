<x-layouts.auth>
    <h2 class="text-2xl font-bold text-ivy-navy">Reset password</h2>
    <p class="mt-2 text-sm text-slate-600">Enter your account email and a secure reset link will be sent if the account exists.</p>

    @if (session('status'))
        <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

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
