<section>
    <x-print-header
        :title="str_replace('-', ' ', ucfirst($document))"
        :subtitle="$sale->sale_number.' · '.$sale->sale_date->format('d M Y H:i')"
    />
    <p class="mt-2 text-sm text-slate-600">Customer: {{ $sale->customer?->name ?? 'Walk-in customer' }}</p>
    <table class="mt-4 w-full border-collapse text-sm">
        <thead>
            <tr>
                <th class="border-b border-slate-300 px-2 py-2 text-left">Product</th>
                <th class="border-b border-slate-300 px-2 py-2 text-left">Qty</th>
                <th class="border-b border-slate-300 px-2 py-2 text-left">Price</th>
                <th class="border-b border-slate-300 px-2 py-2 text-left">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td class="border-b border-slate-100 px-2 py-2">{{ $item->productVariant->name }}</td>
                    <td class="border-b border-slate-100 px-2 py-2">{{ $item->quantity }}</td>
                    <td class="border-b border-slate-100 px-2 py-2">GHS {{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="border-b border-slate-100 px-2 py-2">GHS {{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="total mt-3 text-lg font-bold text-ivy-navy">Total: GHS {{ number_format((float) $sale->total, 2) }}</p>
    <p class="text-sm text-slate-600">Paid: GHS {{ number_format((float) $sale->amount_paid, 2) }} · Balance: GHS {{ number_format((float) $sale->balance_due, 2) }}</p>
    @if ($document === 'invoice' && $sale->creditInvoice)
        <p class="mt-2 text-sm text-slate-600">Invoice: {{ $sale->creditInvoice->invoice_number }} · Due {{ $sale->creditInvoice->due_date->format('d M Y') }}</p>
    @endif
    @if ($document === 'delivery-note' && $sale->deliveryNote)
        <p class="mt-2 text-sm text-slate-600">Delivery note: {{ $sale->deliveryNote->note_number }}</p>
    @endif
    <x-print-footer />
</section>
