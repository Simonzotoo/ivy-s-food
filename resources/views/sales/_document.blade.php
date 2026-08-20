<section>
    <h1>Ivy's Food</h1>
    <p>{{ str_replace('-', ' ', ucfirst($document)) }} · {{ $sale->sale_number }}</p>
    <p>{{ $sale->sale_date->format('d M Y H:i') }}</p>
    <p>Customer: {{ $sale->customer?->name ?? 'Walk-in customer' }}</p>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->productVariant->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>GHS {{ $item->unit_price }}</td>
                    <td>GHS {{ $item->line_total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="total">Total: GHS {{ $sale->total }}</p>
    <p>Paid: GHS {{ $sale->amount_paid }} · Balance: GHS {{ $sale->balance_due }}</p>
    @if ($document === 'invoice' && $sale->creditInvoice)
        <p>Invoice: {{ $sale->creditInvoice->invoice_number }} · Due {{ $sale->creditInvoice->due_date->format('d M Y') }}</p>
    @endif
    @if ($document === 'delivery-note' && $sale->deliveryNote)
        <p>Delivery note: {{ $sale->deliveryNote->note_number }}</p>
    @endif
</section>
