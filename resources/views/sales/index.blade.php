<x-layouts.app title="Sales">
    <div class="mb-5 flex justify-end">
        @can('sales.create')
            <a href="{{ route('sales.pos') }}" class="btn-primary">New sale</a>
        @endcan
    </div>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($sales as $sale)
                <article class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-bold text-ivy-navy">{{ $sale->sale_number }}</p>
                        <p class="text-sm text-slate-600">{{ $sale->sale_date->format('d M Y H:i') }} · {{ $sale->customer?->name ?? 'Walk-in customer' }} · {{ $sale->saleType->name }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($sale->payment_status) }}</span>
                        <p class="font-bold text-ivy-navy">GHS {{ $sale->total }}</p>
                        <a href="{{ route('sales.show', $sale) }}" class="btn-secondary">Open</a>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No sales have been recorded yet.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $sales->links() }}</div>
</x-layouts.app>
