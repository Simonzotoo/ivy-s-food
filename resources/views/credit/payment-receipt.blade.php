<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #101317; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #d1d5db; padding: 8px; text-align: left; }
        .total { font-weight: bold; font-size: 16px; color: #2d4054; }
    </style>
</head>
<body>
    <x-print-header
        title="Payment Receipt"
        :subtitle="$payment->payment_number.' · '.$payment->received_at->format('d M Y H:i')"
    />
    <p>Customer: {{ $payment->customer->name }}</p>
    <p>Payment method: {{ $payment->payment_method }}{{ $payment->transaction_reference ? ' · Ref: '.$payment->transaction_reference : '' }}</p>
    <p class="total">Amount received: GHS {{ number_format((float) $payment->amount, 2) }}</p>

    <h2 style="color:#2d4054;font-size:14px;margin-top:20px;">Applied to invoices</h2>
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Amount applied</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payment->allocations as $allocation)
                <tr>
                    <td>{{ $allocation->invoice->invoice_number }}</td>
                    <td>GHS {{ number_format((float) $allocation->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No invoices were outstanding for this payment.</td></tr>
            @endforelse
        </tbody>
    </table>

    <x-print-footer />
</body>
</html>
