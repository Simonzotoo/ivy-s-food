<x-layouts.app title="Materials Used">
    <p class="mb-5 text-sm text-slate-500">Every raw-material stock movement - purchases received, materials used in production, returns and adjustments.</p>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($movements as $movement)
                <article class="p-4 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold text-ivy-navy">{{ $movement->material->name }}</p>
                        <p class="font-bold text-ivy-navy">{{ $movement->quantity }}</p>
                    </div>
                    <p class="mt-1 text-slate-500">{{ str_replace('_', ' ', $movement->movement_type) }} · {{ $movement->occurred_at->format('d M Y H:i') }} · Batch: {{ $movement->batch?->lot_number ?: 'No lot' }}</p>
                    @if ($movement->notes)
                        <p class="mt-1 text-slate-600">{{ $movement->notes }}</p>
                    @endif
                </article>
            @empty
                <div class="px-4 py-10 text-center text-slate-500">No material movements yet.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $movements->links() }}</div>
    </div>
</x-layouts.app>
