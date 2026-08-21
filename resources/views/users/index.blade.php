<x-layouts.app title="Users">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Staff accounts are created by authorized administrators only.</p>
        @can('users.create')
            <a href="{{ route('users.create') }}" class="btn-primary">Create user</a>
        @endcan
    </div>
    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Roles</th>
                        <th class="px-4 py-3">Last login</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ivy-navy">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $user->email }}{{ $user->phone ? ' · '.$user->phone : '' }}</p>
                            </td>
                            <td class="px-4 py-3"><span class="badge {{ $user->status === 'active' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($user->status) }}</span></td>
                            <td class="px-4 py-3">{{ $user->roles->pluck('name')->join(', ') ?: 'No role' }}</td>
                            <td class="px-4 py-3">{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @can('users.edit')
                                        <a href="{{ route('users.edit', $user) }}" class="btn-secondary">Edit</a>
                                    @endcan
                                    @can('users.activate_suspend')
                                        <x-confirm-form
                                            action="{{ route('users.status', $user) }}"
                                            trigger="{{ $user->status === 'active' ? 'Suspend' : 'Activate' }}"
                                            message="{{ $user->status === 'active' ? $user->name.' will no longer be able to sign in.' : 'Allow '.$user->name.' to sign in again?' }}"
                                            confirm-label="Yes, {{ strtolower($user->status === 'active' ? 'suspend' : 'activate') }}"
                                        >
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'suspended' : 'active' }}">
                                        </x-confirm-form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $users->links() }}</div>
    </div>
</x-layouts.app>
