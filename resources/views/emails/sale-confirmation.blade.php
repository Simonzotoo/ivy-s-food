<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sale confirmation - {{ $sale->sale_number }}</title>
</head>
<body style="margin:0;padding:24px;background:#f6f8fb;font-family:Arial, Helvetica, sans-serif;color:#101317;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;padding:24px;">
        <x-print-header
            title="Sale confirmation"
            :subtitle="$sale->sale_number.' · '.$sale->sale_date->format('d M Y H:i')"
        />
        <p style="font-size:13px;margin:0 0 12px 0;">
            Customer: {{ $sale->customer?->name ?? 'Walk-in customer' }}
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr>
                    <th style="text-align:left;border-bottom:1px solid #d1d5db;padding:6px 4px;">Product</th>
                    <th style="text-align:left;border-bottom:1px solid #d1d5db;padding:6px 4px;">Qty</th>
                    <th style="text-align:left;border-bottom:1px solid #d1d5db;padding:6px 4px;">Price</th>
                    <th style="text-align:left;border-bottom:1px solid #d1d5db;padding:6px 4px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td style="border-bottom:1px solid #f1f5f9;padding:6px 4px;">{{ $item->productVariant->name }}</td>
                        <td style="border-bottom:1px solid #f1f5f9;padding:6px 4px;">{{ $item->quantity }}</td>
                        <td style="border-bottom:1px solid #f1f5f9;padding:6px 4px;">GHS {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td style="border-bottom:1px solid #f1f5f9;padding:6px 4px;">GHS {{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="font-size:16px;font-weight:bold;color:#2d4054;margin:14px 0 4px 0;">Total: GHS {{ number_format((float) $sale->total, 2) }}</p>
        <p style="font-size:13px;color:#475569;margin:0;">Paid: GHS {{ number_format((float) $sale->amount_paid, 2) }} &middot; Balance: GHS {{ number_format((float) $sale->balance_due, 2) }}</p>
        <x-print-footer />
    </div>
</body>
</html>
