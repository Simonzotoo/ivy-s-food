@csrf
@if ($managedUser ?? false)
    @method('PUT')
@endif
<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="form-label" for="name">Full name</label>
        <input class="form-input" id="name" name="name" value="{{ old('name', $managedUser->name ?? '') }}" required>
    </div>
    <div>
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $managedUser->email ?? '') }}" required>
    </div>
    <div>
        <label class="form-label" for="phone">Phone number</label>
        <input class="form-input" id="phone" name="phone" value="{{ old('phone', $managedUser->phone ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="password">{{ ($managedUser ?? false) ? 'New password' : 'Temporary password' }}</label>
        <x-password-toggle id="password" name="password" autocomplete="new-password" @if(! ($managedUser ?? false)) required @endif />
    </div>
    <div>
        <label class="form-label" for="password_confirmation">Confirm password</label>
        <x-password-toggle id="password_confirmation" name="password_confirmation" autocomplete="new-password" @if(! ($managedUser ?? false)) required @endif />
    </div>
    <label class="mt-7 flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="password_change_required" value="1" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red" @checked(old('password_change_required', $managedUser->password_change_required ?? true))>
        Require password change
    </label>
</div>
<div class="mt-6">
    <p class="form-label">Roles</p>
    <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($roles as $role)
            <label class="flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red"
                    @checked(in_array($role->id, old('role_ids', isset($managedUser) ? $managedUser->roles->pluck('id')->all() : [])))>
                {{ $role->name }}
            </label>
        @endforeach
    </div>
</div>
<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a>
    <button class="btn-primary" type="submit">Save user</button>
</div>
