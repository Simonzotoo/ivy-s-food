<x-layouts.app title="Recipes">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Versioned recipes control what can move into live production.</p>
        @can('recipes.manage')
            <a href="{{ route('recipes.create') }}" class="btn-primary">Create recipe</a>
        @endcan
    </div>

    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($recipes as $recipe)
                <article class="p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="font-bold text-ivy-navy">{{ $recipe->productVariant->name }}</p>
                            <p class="mt-1 text-sm text-slate-600">Version {{ $recipe->version }} · {{ $recipe->materials_count ?? $recipe->materials->count() }} lines</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $recipe->status === 'approved' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($recipe->status) }}</span>
                            <a href="{{ route('recipes.show', $recipe) }}" class="btn-secondary">Open</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No recipes have been configured yet.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $recipes->links() }}</div>
</x-layouts.app>
