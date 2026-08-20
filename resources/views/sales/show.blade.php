<x-layouts.app title="{{ $sale->sale_number }}">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-slate-500">{{ $sale->customer?->name ?? 'Walk-in customer' }}</p>
                    <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $sale->sale_number }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $sale->sale_date->format('d M Y H:i') }} · {{ ucfirst($sale->status) }}</p>
                </div>
                <p class="text-xl font-bold text-ivy-navy">GHS {{ $sale->total }}</p>
            </div>
            <div class="mt-6 divide-y divide-slate-100 md:hidden">
                @foreach ($sale->items as $item)
                    <article class="py-3">
                        <p class="font-semibold text-ivy-navy">{{ $item->productVariant->name }}</p>
                        <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Qty</dt><dd>{{ $item->quantity }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Unit price</dt><dd>GHS {{ $item->unit_price }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Discount</dt><dd>GHS {{ $item->discount_amount }}</dd></div>
                            <div><dt class="text-xs font-semibold uppercase text-slate-500">Total</dt><dd>GHS {{ $item->line_total }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>
            <div class="mt-6 hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Unit price</th>
                            <th class="px-4 py-3">Discount</th>
                            <th class="px-4 py-3">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ivy-navy">{{ $item->productVariant->name }}</td>
                                <td class="px-4 py-3">{{ $item->quantity }}</td>
                                <td class="px-4 py-3">GHS {{ $item->unit_price }}</td>
                                <td class="px-4 py-3">GHS {{ $item->discount_amount }}</td>
                                <td class="px-4 py-3">GHS {{ $item->line_total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-bold text-ivy-navy">Documents</h3>
                <div class="mt-4 grid gap-2">
                    <a class="btn-secondary" href="{{ route('sales.receipt', $sale) }}">Receipt</a>
                    <a class="btn-secondary" href="{{ route('sales.pdf', [$sale, 'receipt']) }}">Receipt PDF</a>
                    @if ($sale->creditInvoice)
                        <a class="btn-secondary" href="{{ route('sales.pdf', [$sale, 'invoice']) }}">Invoice PDF</a>
                    @endif
                    <a class="btn-secondary" href="{{ route('sales.pdf', [$sale, 'delivery-note']) }}">Delivery note PDF</a>
                </div>
            </section>
            @can('sales.return')
                @if ($sale->status === 'completed')
                    <form method="POST" action="{{ route('sales.returns.store', $sale) }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
                        @csrf
                        <h3 class="text-base font-bold text-ivy-navy">Return item</h3>
                        <label class="form-label mt-4" for="sale_item_id">Item</label>
                        <select class="form-input" id="sale_item_id" name="items[0][sale_item_id]" required>
                            @foreach ($sale->items as $item)
                                <option value="{{ $item->id }}">{{ $item->productVariant->name }}</option>
                            @endforeach
                        </select>
                        <label class="form-label mt-4" for="quantity">Quantity</label>
                        <input class="form-input" id="quantity" name="items[0][quantity]" type="number" min="0.001" step="0.001" required>
                        <label class="form-label mt-4" for="return_date">Return date</label>
                        <input class="form-input" id="return_date" name="return_date" type="date" value="{{ now()->toDateString() }}" required>
                        <label class="form-label mt-4" for="reason">Reason</label>
                        <textarea class="form-input" id="reason" name="reason" rows="3" required></textarea>
                        <button class="btn-primary mt-5 w-full" type="submit">Record return</button>
                    </form>
                @endif
            @endcan
            @can('sales.reverse')
                @if ($sale->status === 'completed')
                    <form method="POST" action="{{ route('sales.reverse', $sale) }}" class="rounded-md border border-red-200 bg-white p-6 shadow-sm">
                        @csrf
                        <h3 class="text-base font-bold text-ivy-navy">Reverse sale</h3>
                        <label class="form-label mt-4" for="reverse_reason">Reason</label>
                        <textarea class="form-input" id="reverse_reason" name="reason" rows="3" required></textarea>
                        <button class="btn-secondary mt-5 w-full" type="submit">Reverse sale</button>
                    </form>
                @endif
            @endcan
        </aside>
    </div>
</x-layouts.app>
