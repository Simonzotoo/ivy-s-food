<x-layouts.app title="Create User">
    <form method="POST" action="{{ route('users.store') }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @include('users._form')
    </form>
</x-layouts.app>
