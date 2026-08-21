<x-layouts.app title="Production Batches">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('production.plans') }}" class="btn-secondary">Plans</a>
        @can('production.start')
            <a href="{{ route('production.batches.create') }}" class="btn-primary">Create batch</a>
        @endcan
    </div>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($batches as $batch)
                <article class="p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-bold text-ivy-navy">{{ $batch->batch_number }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $batch->productVariant->name }} · {{ $batch->production_date->format('d M Y') }} · {{ $batch->planned_quantity }} planned</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $batch->status === 'completed' ? 'badge-success' : ($batch->status === 'reversed' ? '' : 'badge-warning') }}">{{ ucfirst($batch->status) }}</span>
                            <a href="{{ route('production.batches.show', $batch) }}" class="btn-secondary">Open</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No production batches yet.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $batches->links() }}</div>
</x-layouts.app>
