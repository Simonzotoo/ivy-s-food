<x-layouts.app title="Roles & Permissions">
    <div class="space-y-5">
        @foreach ($roles as $role)
            <form method="POST" action="{{ route('roles.update', $role) }}" class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PUT')
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="form-label" for="role-name-{{ $role->id }}">Role name</label>
                        <input class="form-input" id="role-name-{{ $role->id }}" name="name" value="{{ old('name', $role->name) }}" @disabled($role->is_owner)>
                    </div>
                    <div>
                        <label class="form-label" for="role-description-{{ $role->id }}">Description</label>
                        <input class="form-input" id="role-description-{{ $role->id }}" name="description" value="{{ old('description', $role->description) }}">
                    </div>
                </div>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    @foreach ($permissions as $group => $groupPermissions)
                        <fieldset class="rounded-md border border-slate-200 p-4">
                            <legend class="px-1 text-sm font-bold text-ivy-navy">{{ $group }}</legend>
                            <div class="mt-2 space-y-2">
                                @foreach ($groupPermissions as $permission)
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red"
                                            @checked($role->is_owner || $role->permissions->contains($permission))
                                            @disabled($role->is_owner)>
                                        {{ $permission->name }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                @can('roles.manage')
                    <div class="mt-5 flex justify-end">
                        <button class="btn-primary" type="submit" @disabled($role->is_owner && ! auth()->user()->isOwner())>Save role</button>
                    </div>
                @endcan
            </form>
        @endforeach
    </div>
</x-layouts.app>
