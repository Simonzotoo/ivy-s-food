<x-layouts.app title="Finished Batches">
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
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
