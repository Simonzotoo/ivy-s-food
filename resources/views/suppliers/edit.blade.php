<x-layouts.app title="Edit Supplier">
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @include('suppliers._form')
    </form>
</x-layouts.app>
