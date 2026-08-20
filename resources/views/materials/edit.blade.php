<x-layouts.app title="Edit Material">
    <form method="POST" action="{{ route('materials.update', $material) }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @include('materials._form')
    </form>
</x-layouts.app>
