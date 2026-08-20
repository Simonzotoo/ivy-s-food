<x-layouts.app title="Sales">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-ivy-navy">Sales</h2>
            <p class="mt-1 text-sm text-slate-600">Record new sales and reopen receipts or invoices when customers need them.</p>
        </div>
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
                <div class="px-5 py-12 text-center">
                    <p class="font-semibold text-ivy-navy">No sales recorded yet.</p>
                    @can('sales.create')
                        <a href="{{ route('sales.pos') }}" class="mt-4 btn-primary">Record first sale</a>
                    @endcan
                </div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $sales->links() }}</div>
</x-layouts.app>
