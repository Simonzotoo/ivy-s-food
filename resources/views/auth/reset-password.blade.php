<x-layouts.auth>
    <h2 class="text-2xl font-bold text-ivy-navy">Choose a new password</h2>
    <p class="mt-2 text-sm text-slate-600">Use a strong password that is not shared with any other service.</p>

    @if ($errors->any())
        <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required class="form-input">
        </div>
        <div>
            <label for="password" class="form-label">New password</label>
            <input id="password" name="password" type="password" required class="form-input">
        </div>
        <div>
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="form-input">
        </div>
        <button class="btn-primary w-full" type="submit">Reset password</button>
    </form>
</x-layouts.auth>
