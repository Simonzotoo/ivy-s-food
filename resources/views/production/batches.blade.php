<x-layouts.app title="Production">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-ivy-navy">Production</h2>
            <p class="mt-1 text-sm text-slate-600">Start production batches, record output and keep batch history.</p>
        </div>
        <div class="flex flex-wrap gap-2">
        <a href="{{ route('production.plans') }}" class="btn-secondary">Production plans</a>
        @can('production.start')
            <a href="{{ route('production.batches.create') }}" class="btn-primary">Record production</a>
        @endcan
        </div>
    </div>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($batches as $batch)
                <article class="p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-bold text-ivy-navy">{{ $batch->batch_number }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $batch->productVariant->name }} · {{ $batch->production_date->format('d M Y') }} · {{ $batch->planned_quantity }} planned quantity</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $batch->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($batch->status === 'reversed' ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-800') }}">{{ ucfirst($batch->status) }}</span>
                            <a href="{{ route('production.batches.show', $batch) }}" class="btn-secondary">Open</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center">
                    <p class="font-semibold text-ivy-navy">No production recorded yet.</p>
                    @can('production.start')
                        <a href="{{ route('production.batches.create') }}" class="mt-4 btn-primary">Record first production</a>
                    @endcan
                </div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $batches->links() }}</div>
</x-layouts.app>
