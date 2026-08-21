<x-layouts.app title="Purchase {{ $purchase->purchase_number }}">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-slate-500">{{ $purchase->supplier->name }}</p>
                    <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $purchase->purchase_number }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $purchase->purchase_date->format('d M Y') }} · {{ ucfirst($purchase->status) }}</p>
                </div>
                <p class="text-lg font-bold text-ivy-navy">GHS {{ $purchase->subtotal }}</p>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="px-3 py-2">Material</th>
                            <th class="px-3 py-2">Qty</th>
                            <th class="px-3 py-2">Unit cost</th>
                            <th class="px-3 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($purchase->items as $item)
                            <tr>
                                <td class="px-3 py-2">{{ $item->material->name }}</td>
                                <td class="px-3 py-2">{{ $item->quantity }} {{ $item->unit->symbol }}</td>
                                <td class="px-3 py-2">GHS {{ $item->unit_cost }}</td>
                                <td class="px-3 py-2">GHS {{ $item->line_total }}</td>
                            </tr>
                            @foreach ($item->batches as $batch)
                                <tr class="bg-slate-50">
                                    <td colspan="4" class="px-3 py-2 text-xs text-slate-600">
                                        Batch {{ $batch->lot_number ?: '#'.$batch->id }} · Available {{ $batch->quantity_available }} · Expires {{ $batch->expiry_date?->format('d M Y') ?? 'Not set' }}
                                        @can('purchases.return')
                                            <form method="POST" action="{{ route('material-batches.return', $batch) }}" class="mt-2 grid gap-2 sm:grid-cols-[120px_140px_1fr_auto]">
                                                @csrf
                                                <input class="form-input mt-0" name="quantity" type="number" min="0.001" step="0.001" placeholder="Qty" required>
                                                <input class="form-input mt-0" name="return_date" type="date" value="{{ now()->toDateString() }}" required>
                                                <input class="form-input mt-0" name="reason" placeholder="Return reason" required>
                                                <button class="btn-secondary" type="submit">Return</button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($purchase->attachments->isNotEmpty())
                <div class="mt-6 rounded-md border border-slate-200 bg-slate-50 p-4">
                    <h3 class="text-sm font-bold text-ivy-navy">Attachment references</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        @foreach ($purchase->attachments as $attachment)
                            <li>{{ $attachment->original_name }} <span class="text-slate-500">({{ $attachment->path }})</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
        @can('purchases.receive')
            <aside class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-ivy-navy">Receive Purchase</h2>
                @if ($purchase->status === 'received')
                    <p class="mt-3 text-sm text-slate-600">This purchase has already been received.</p>
                @else
                    <form method="POST" action="{{ route('purchases.receive', $purchase) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="form-label" for="received_date">Received date</label>
                            <input class="form-input" id="received_date" name="received_date" type="date" value="{{ now()->toDateString() }}" required>
                        </div>
                        @foreach ($purchase->items as $item)
                            <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-ivy-navy">{{ $item->material->name }}</p>
                                <input type="hidden" name="batches[{{ $loop->index }}][purchase_item_id]" value="{{ $item->id }}">
                                <label class="form-label mt-3" for="lot-{{ $item->id }}">Lot number</label>
                                <input class="form-input" id="lot-{{ $item->id }}" name="batches[{{ $loop->index }}][lot_number]">
                                <label class="form-label mt-3" for="expiry-{{ $item->id }}">Expiry date</label>
                                <input class="form-input" id="expiry-{{ $item->id }}" name="batches[{{ $loop->index }}][expiry_date]" type="date">
                            </div>
                        @endforeach
                        <button class="btn-primary w-full" type="submit">Receive purchase</button>
                    </form>
                @endif
            </aside>
        @endcan
    </div>
</x-layouts.app>
