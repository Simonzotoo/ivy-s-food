<x-layouts.app title="Stock History">
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Raw-material movements</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($rawMovements as $movement)
                    <div class="py-3 text-sm">
                        <p class="font-semibold text-ivy-navy">{{ $movement->material->name }} · {{ $movement->quantity }}</p>
                        <p class="text-slate-500">{{ $movement->movement_type }} · {{ $movement->occurred_at->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No raw movements yet.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Finished-product movements</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($finishedMovements as $movement)
                    <div class="py-3 text-sm">
                        <p class="font-semibold text-ivy-navy">{{ $movement->productVariant->name }} · {{ $movement->quantity }}</p>
                        <p class="text-slate-500">{{ $movement->movement_type }} · {{ $movement->occurred_at->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No finished movements yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
