<x-layouts.app title="Create Supplier">
    <form method="POST" action="{{ route('suppliers.store') }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @include('suppliers._form')
    </form>
</x-layouts.app>
