<x-layouts.app title="Import History">
    <p class="mb-6 max-w-3xl text-sm text-slate-600">Every import that has been uploaded, from any page. Look up a past import to review what happened, download its errors, or roll it back.</p>

    @can('imports.history')
        <section class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse ($batches as $batch)
                    <article class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <a class="font-semibold text-ivy-navy" href="{{ route('imports.show', $batch) }}">{{ $batch->batch_number }}</a>
                            <p class="text-sm text-slate-600">{{ $types[$batch->import_type]['label'] ?? $batch->import_type }} · {{ $batch->original_filename ?? 'Pasted data' }}</p>
                        </div>
                        <span class="badge">{{ str_replace('_', ' ', $batch->status) }}</span>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center text-slate-500">No imports have been run yet. Look for an "Import" button on the Customers, Suppliers, Materials, Products, Inventory or Credit pages.</div>
                @endforelse
            </div>
            <div class="p-5">{{ $batches->links() }}</div>
        </section>
    @endcan
</x-layouts.app>
