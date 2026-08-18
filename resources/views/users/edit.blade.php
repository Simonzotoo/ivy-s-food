<x-layouts.app title="Edit User">
    <form method="POST" action="{{ route('users.update', $managedUser) }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @include('users._form', ['managedUser' => $managedUser])
    </form>
</x-layouts.app>
