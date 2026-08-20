<x-layouts.app title="Customers Owing">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-ivy-navy">Customers owing</h2>
            <p class="mt-1 text-sm text-slate-600">See credit sales, due dates and unpaid balances.</p>
        </div>
        <div class="flex flex-wrap gap-2">
        <a href="{{ route('credit.payments') }}" class="btn-primary">Receive payment</a>
        <a href="{{ route('credit.aging') }}" class="btn-secondary">Aging report</a>
        @can('credit.reminders')
            <a href="{{ route('credit.reminders') }}" class="btn-secondary">Reminders</a>
        @endcan
        </div>
    </div>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($invoices as $invoice)
                <article class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-bold text-ivy-navy">{{ $invoice->invoice_number }}</p>
                        <p class="text-sm text-slate-600">{{ $invoice->customer->name }} · Due {{ $invoice->due_date->format('d M Y') }} · {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="font-bold text-ivy-navy">GHS {{ $invoice->balance }}</p>
                        <a href="{{ route('credit.invoices.show', $invoice) }}" class="btn-secondary">Open</a>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No customers currently owe money.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $invoices->links() }}</div>
</x-layouts.app>
