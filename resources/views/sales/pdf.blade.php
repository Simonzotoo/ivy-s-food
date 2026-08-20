<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { color: #0f2742; font-size: 20px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #d1d5db; padding: 8px; text-align: left; }
        .total { font-weight: bold; font-size: 16px; color: #0f2742; }
    </style>
</head>
<body>
    @include('sales._document', ['sale' => $sale, 'document' => $document])
</body>
</html>
