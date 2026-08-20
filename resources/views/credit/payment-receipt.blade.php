<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111827}h1{color:#0f2742}</style></head>
<body>
    <h1>Ivy's Food Payment Receipt</h1>
    <p>{{ $payment->payment_number }}</p>
    <p>Customer: {{ $payment->customer->name }}</p>
    <p>Amount: GHS {{ $payment->amount }}</p>
    <p>Method: {{ $payment->payment_method }}</p>
    <h2>Allocations</h2>
    @foreach ($payment->allocations as $allocation)
        <p>{{ $allocation->invoice->invoice_number }} · GHS {{ $allocation->amount }}</p>
    @endforeach
</body>
</html>
