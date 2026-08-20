<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->sale_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white p-6 text-ivy-black">
    @include('sales._document', ['sale' => $sale, 'document' => 'receipt'])
    <div class="mt-6 print:hidden">
        <a class="btn-secondary" href="{{ route('sales.show', $sale) }}">Back</a>
        <button class="btn-primary" onclick="window.print()">Print</button>
    </div>
</body>
</html>
