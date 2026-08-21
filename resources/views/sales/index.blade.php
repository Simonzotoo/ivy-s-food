<x-layouts.app title="Sales">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="grid flex-1 gap-3 md:grid-cols-[1fr_180px_160px_auto]">
            <input class="form-input mt-0" name="search" value="{{ request('search') }}" placeholder="Search sale number or customer">
            <select class="form-input mt-0" name="sale_type">
                <option value="">All sale types</option>
                @foreach ($saleTypes as $saleType)
                    <option value="{{ $saleType->id }}" @selected((string) request('sale_type') === (string) $saleType->id)>{{ $saleType->name }}</option>
                @endforeach
            </select>
            <select class="form-input mt-0" name="payment_status">
                <option value="">All payment statuses</option>
                @foreach (['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-secondary" type="submit">Filter</button>
        </form>
        @can('sales.create')
            <a href="{{ route('sales.pos') }}" class="btn-primary">New Sale</a>
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
                        <span class="badge {{ $sale->payment_status === 'paid' ? 'badge-success' : ($sale->payment_status === 'unpaid' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($sale->payment_status) }}</span>
                        <p class="font-bold text-ivy-navy">GHS {{ number_format((float) $sale->total, 2) }}</p>
                        <a href="{{ route('sales.show', $sale) }}" class="btn-secondary">Open</a>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">
                    @if (request()->hasAny(['search', 'sale_type', 'payment_status']))
                        No sales match these filters.
                    @else
                        No sales have been recorded yet. Click "New Sale" to record the first one.
                    @endif
                </div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $sales->links() }}</div>
</x-layouts.app>
