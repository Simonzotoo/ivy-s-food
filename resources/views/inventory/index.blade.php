<x-layouts.app title="Inventory">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-ivy-navy">Inventory</h2>
            <p class="mt-1 text-sm text-slate-600">Check raw materials, finished products, expiry and stock counts.</p>
        </div>
        <div class="flex flex-wrap gap-2">
        <a href="{{ route('inventory.finished-batches') }}" class="btn-secondary">Finished stock</a>
        <a href="{{ route('inventory.movements') }}" class="btn-secondary">Stock history</a>
        @can('inventory.adjust')
            <a href="{{ route('inventory.adjustments') }}" class="btn-secondary">Adjustments</a>
        @endcan
        <a href="{{ route('inventory.stocktakes') }}" class="btn-secondary">Stock count</a>
        </div>
    </div>

    @if ($pendingOutputs->isNotEmpty())
        <section class="mb-6 rounded-md border border-amber-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Production waiting to enter stock</h2>
            <div class="mt-3 space-y-3">
                @foreach ($pendingOutputs as $output)
                    <div class="flex flex-col gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-700">{{ $output->productVariant->name }} · {{ $output->accepted_quantity }} units · {{ $output->productionBatch->batch_number }}</p>
                        @can('inventory.post_production')
                            <form method="POST" action="{{ route('inventory.production-outputs.post', $output) }}">
                                @csrf
                                <button class="btn-primary" type="submit">Add to stock</button>
                            </form>
                        @endcan
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Raw materials</h2>
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
                    <p class="py-8 text-center text-sm text-slate-500">No raw materials have been added yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Finished products</h2>
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
                    <p class="py-8 text-center text-sm text-slate-500">No finished products are currently in stock.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
