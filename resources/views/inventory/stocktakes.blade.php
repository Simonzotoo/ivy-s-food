<x-layouts.app title="Stocktakes">
    <div class="mb-5 flex justify-end">
        @can('inventory.stocktake')
            <a href="{{ route('inventory.stocktakes.create') }}" class="btn-primary">Create stocktake</a>
        @endcan
    </div>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($stocktakes as $stocktake)
                <article class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-bold text-ivy-navy">{{ ucfirst($stocktake->stock_type) }} stocktake · {{ $stocktake->stocktake_date->format('d M Y') }}</p>
                        <p class="text-sm text-slate-600">{{ $stocktake->items_count }} item(s) · {{ ucfirst(str_replace('_', ' ', $stocktake->status)) }}</p>
                    </div>
                    @can('inventory.approve_stocktake')
                        @if ($stocktake->status === 'pending_review')
                            <form method="POST" action="{{ route('inventory.stocktakes.approve', $stocktake) }}">
                                @csrf
                                <button class="btn-primary" type="submit">Approve variances</button>
                            </form>
                        @endif
                    @endcan
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No stocktakes yet.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $stocktakes->links() }}</div>
</x-layouts.app>
