<x-layouts.app title="Credit Aging">
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach (['not_due', 'due_soon', 'due_today', 'overdue', 'partially_paid'] as $status)
            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-ivy-navy">{{ ucfirst(str_replace('_', ' ', $status)) }}</h2>
                <p class="mt-2 text-2xl font-bold text-ivy-navy">GHS {{ number_format((float) ($invoices[$status] ?? collect())->sum('balance'), 2) }}</p>
                <div class="mt-4 divide-y divide-slate-100 text-sm">
                    @foreach (($invoices[$status] ?? collect()) as $invoice)
                        <div class="py-2">{{ $invoice->customer->name }} · {{ $invoice->invoice_number }}</div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-layouts.app>
