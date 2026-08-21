<x-layouts.app title="Inventory">
    <p class="mb-5 text-sm text-slate-500">What's available, what's running low, and what needs attention. The full stock history is a click away.</p>

    <div class="mb-5 flex flex-wrap gap-3">
        <a href="{{ route('inventory.finished-batches') }}" class="btn-secondary">Finished batches</a>
        <a href="{{ route('inventory.movements') }}" class="btn-secondary">Stock History</a>
        @can('inventory.adjust')
            <a href="{{ route('inventory.adjustments') }}" class="btn-secondary">Adjust Stock</a>
        @endcan
        <a href="{{ route('inventory.stocktakes') }}" class="btn-secondary">Stocktakes</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card">
            <p class="metric-label">Low stock</p>
            <p class="metric-value">{{ $lowRawMaterials->count() + $lowFinishedProducts->count() }}</p>
            <p class="metric-note">At or below reorder level</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Expiring soon</p>
            <p class="metric-value">{{ $nearExpiryFinished->count() }}</p>
            <p class="metric-note">Within the alert window</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Already expired</p>
            <p class="metric-value">{{ $expiredFinished->count() }}</p>
            <p class="metric-note">Still marked available</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Damaged / quarantined</p>
            <p class="metric-value">{{ $needsAttention->count() }}</p>
            <p class="metric-note">Finished batches to review</p>
        </div>
    </div>

    @if ($pendingOutputs->isNotEmpty())
        <section class="mt-6 rounded-md border border-amber-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Pending production output</h2>
            <div class="mt-3 space-y-3">
                @foreach ($pendingOutputs as $output)
                    <div class="flex flex-col gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-700">{{ $output->productVariant->name }} · {{ $output->accepted_quantity }} units · {{ $output->productionBatch->batch_number }}</p>
                        @can('inventory.post_production')
                            <form method="POST" action="{{ route('inventory.production-outputs.post', $output) }}">
                                @csrf
                                <button class="btn-primary" type="submit">Post to inventory</button>
                            </form>
                        @endcan
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Low stock</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Raw materials</h3>
                    @forelse ($lowRawMaterials as $material)
                        <p class="mt-2 text-sm">{{ $material->name }} · {{ $material->availableQuantity() }} {{ $material->baseUnit->symbol }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Nothing is low right now.</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Finished products</h3>
                    @forelse ($lowFinishedProducts as $variant)
                        <p class="mt-2 text-sm">{{ $variant->name }} · {{ number_format((float) $variant->finishedBatches->sum('quantity_available'), 3) }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Nothing is low right now.</p>
                    @endforelse
                </div>
            </div>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-ivy-navy">Expiry</h2>
                <a href="{{ route('inventory.finished-batches') }}" class="metric-link">View all batches</a>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Expiring soon</h3>
                    @forelse ($nearExpiryFinished->take(6) as $batch)
                        <p class="mt-2 text-sm">{{ $batch->productVariant->name }} · {{ $batch->expiry_date?->format('d M Y') }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Nothing is expiring soon.</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Already expired</h3>
                    @forelse ($expiredFinished->take(6) as $batch)
                        <p class="mt-2 text-sm text-ivy-red">{{ $batch->productVariant->name }} · {{ $batch->expiry_date?->format('d M Y') }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">No expired finished stock.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    @if ($needsAttention->isNotEmpty())
        <section class="mt-6 rounded-md border border-red-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Damaged or quarantined stock</h2>
            <p class="mt-1 text-sm text-slate-500">These finished batches have damaged or quarantined quantity recorded and may need a decision.</p>
            <div class="mt-4 divide-y divide-slate-100">
                @foreach ($needsAttention as $batch)
                    <div class="flex items-center justify-between gap-3 py-3 text-sm">
                        <p class="font-semibold text-ivy-navy">{{ $batch->productVariant->name }} · {{ $batch->batch_number }}</p>
                        <p class="text-slate-600">Damaged {{ $batch->quantity_damaged }} · Quarantined {{ $batch->quantity_quarantined }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Raw-material balances</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($materials as $material)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div>
                            <p class="font-semibold text-ivy-navy">{{ $material->name }}</p>
                            <p class="text-xs text-slate-500">{{ $material->category->name }}</p>
                        </div>
                        <p class="font-bold text-ivy-navy">{{ $material->availableQuantity() }} {{ $material->baseUnit->symbol }}</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No raw materials configured.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Finished-product balances</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($variants as $variant)
                    @php($available = $variant->finishedBatches->sum('quantity_available'))
                    @if ($available > 0)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-semibold text-ivy-navy">{{ $variant->name }}</p>
                                <p class="text-xs text-slate-500">{{ $variant->finishedBatches->count() }} batch(es)</p>
                            </div>
                            <p class="font-bold text-ivy-navy">{{ number_format($available, 3) }}</p>
                        </div>
                    @endif
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No finished-product stock yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
