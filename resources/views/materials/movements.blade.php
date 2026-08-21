<x-layouts.app title="Materials Used">
    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Material</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Quantity</th>
                        <th class="px-4 py-3">Batch</th>
                        <th class="px-4 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="px-4 py-3">{{ $movement->occurred_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $movement->material->name }}</td>
                            <td class="px-4 py-3">{{ str_replace('_', ' ', $movement->movement_type) }}</td>
                            <td class="px-4 py-3">{{ $movement->quantity }}</td>
                            <td class="px-4 py-3">{{ $movement->batch?->lot_number ?: 'No lot' }}</td>
                            <td class="px-4 py-3">{{ $movement->notes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No material movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $movements->links() }}</div>
    </div>
</x-layouts.app>
