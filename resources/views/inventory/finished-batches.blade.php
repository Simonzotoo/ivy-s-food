<x-layouts.app title="Finished Product Batches">
    <p class="mb-5 text-sm text-slate-500">Every finished-product batch currently on record, with quantity, expiry and value.</p>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($batches as $batch)
                <article class="p-4">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold text-ivy-navy">{{ $batch->batch_number }}</p>
                        <p class="text-sm text-slate-500">{{ $batch->expiry_date?->format('d M Y') ?? 'No expiry set' }}</p>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ $batch->productVariant->name }}</p>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div class="min-w-0"><dt class="text-xs font-semibold uppercase text-slate-500">Available</dt><dd>{{ $batch->quantity_available }}</dd></div>
                        <div class="min-w-0"><dt class="text-xs font-semibold uppercase text-slate-500">Reserved</dt><dd>{{ $batch->quantity_reserved }}</dd></div>
                        <div class="min-w-0"><dt class="text-xs font-semibold uppercase text-slate-500">Value</dt><dd class="font-semibold text-ivy-navy">GHS {{ number_format((float) $batch->quantity_available * (float) $batch->unit_cost, 2) }}</dd></div>
                        <div class="col-span-2 min-w-0"><dt class="text-xs font-semibold uppercase text-slate-500">Damaged, expired &amp; quarantined</dt><dd>{{ $batch->quantity_damaged }} / {{ $batch->quantity_expired }} / {{ $batch->quantity_quarantined }}</dd></div>
                    </dl>
                </article>
            @empty
                <div class="px-4 py-10 text-center text-slate-500">No finished batches yet.</div>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Batch</th>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Available</th>
                        <th class="px-4 py-3">Reserved</th>
                        <th class="px-4 py-3">Damaged/Expired/Quarantined</th>
                        <th class="px-4 py-3">Expiry</th>
                        <th class="px-4 py-3">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($batches as $batch)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-ivy-navy">{{ $batch->batch_number }}</td>
                            <td class="px-4 py-3">{{ $batch->productVariant->name }}</td>
                            <td class="px-4 py-3">{{ $batch->quantity_available }}</td>
                            <td class="px-4 py-3">{{ $batch->quantity_reserved }}</td>
                            <td class="px-4 py-3">{{ $batch->quantity_damaged }} / {{ $batch->quantity_expired }} / {{ $batch->quantity_quarantined }}</td>
                            <td class="px-4 py-3">{{ $batch->expiry_date?->format('d M Y') ?? 'Not set' }}</td>
                            <td class="px-4 py-3">GHS {{ number_format((float) $batch->quantity_available * (float) $batch->unit_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No finished batches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $batches->links() }}</div>
</x-layouts.app>
