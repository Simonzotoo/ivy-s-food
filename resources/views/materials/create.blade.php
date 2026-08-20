<x-layouts.app title="Create Material">
    <form method="POST" action="{{ route('materials.store') }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @include('materials._form')
    </form>
</x-layouts.app>
