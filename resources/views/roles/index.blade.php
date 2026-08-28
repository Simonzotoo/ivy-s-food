<x-layouts.app title="Roles & Permissions">
    <p class="mb-5 text-sm text-slate-500">Choose a role to see or change what that type of staff member can do. Only one role is shown at a time to keep this page manageable.</p>
    <div class="space-y-3" x-data="{ open: null }">
        @foreach ($roles as $role)
            <div class="rounded-md border border-slate-200 bg-white shadow-sm">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left"
                    @click="open = open === {{ $role->id }} ? null : {{ $role->id }}"
                >
                    <div class="min-w-0">
                        <p class="font-bold text-ivy-navy">{{ $role->name }}</p>
                        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $role->description ?: 'No description' }} &middot; {{ $role->permissions->count() }} permissions</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-400 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="{ 'rotate-180': open === {{ $role->id }} }">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open === {{ $role->id }}" x-cloak class="border-t border-slate-200 p-5">
                    <form method="POST" action="{{ route('roles.update', $role) }}">
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
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
