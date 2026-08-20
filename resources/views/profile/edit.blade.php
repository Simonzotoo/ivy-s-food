<x-layouts.app title="Profile">
    <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            @php($profilePhotoUrl = $user->profilePhotoUrl())
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                @if ($profilePhotoUrl)
                    <img src="{{ $profilePhotoUrl }}" alt="{{ $user->name }}" class="h-24 w-24 rounded-full object-cover ring-4 ring-slate-100">
                @else
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-ivy-navy text-2xl font-bold text-white ring-4 ring-slate-100">
                        {{ $user->initials() }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-ivy-navy">Account profile</h2>
                    <p class="mt-1 text-sm text-slate-500">Upload a clear staff photo to make the sidebar and account area easier to recognise.</p>
                    <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="form-label" for="profile_photo">Profile photo</label>
                            <input class="form-input" id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                            <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP. Maximum size 2 MB.</p>
                        </div>
                        <button class="btn-primary" type="submit">Upload photo</button>
                    </form>
                </div>
            </div>
            <dl class="mt-6 space-y-3 border-t border-slate-200 pt-5 text-sm">
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
