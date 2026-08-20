<x-layouts.app title="{{ $customer->name }} Statement">
    <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-bold text-ivy-navy">{{ $customer->name }}</h2>
        <p class="mt-1 text-sm text-slate-600">Outstanding balance: GHS {{ $customer->outstanding_balance }}</p>
        <h3 class="mt-8 text-base font-bold text-ivy-navy">Invoices</h3>
        <div class="mt-3 divide-y divide-slate-100">
            @foreach ($invoices as $invoice)
                <div class="py-3 text-sm">{{ $invoice->invoice_number }} · Total GHS {{ $invoice->total }} · Balance GHS {{ $invoice->balance }}</div>
            @endforeach
        </div>
        <h3 class="mt-8 text-base font-bold text-ivy-navy">Payments</h3>
        <div class="mt-3 divide-y divide-slate-100">
            @foreach ($payments as $payment)
                <div class="py-3 text-sm">{{ $payment->payment_number }} · GHS {{ $payment->amount }}</div>
            @endforeach
        </div>
    </section>
</x-layouts.app>
