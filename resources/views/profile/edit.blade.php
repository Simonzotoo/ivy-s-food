<x-layouts.app title="Profile">
    <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-ivy-navy">Account</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="font-semibold text-slate-500">Name</dt><dd>{{ $user->name }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Phone</dt><dd>{{ $user->phone ?: 'Not set' }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Status</dt><dd>{{ ucfirst($user->status) }}</dd></div>
            </dl>
        </section>
        <form method="POST" action="{{ route('profile.password') }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-bold text-ivy-navy">Change password</h2>
            <div class="mt-5 space-y-4">
                <div>
                    <label class="form-label" for="current_password">Current password</label>
                    <input class="form-input" id="current_password" name="current_password" type="password" required>
                </div>
                <div>
                    <label class="form-label" for="password">New password</label>
                    <input class="form-input" id="password" name="password" type="password" required>
                </div>
                <div>
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" required>
                </div>
                <button class="btn-primary" type="submit">Update password</button>
            </div>
        </form>
    </div>
</x-layouts.app>
