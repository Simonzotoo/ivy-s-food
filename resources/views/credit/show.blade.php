<x-layouts.app title="{{ $invoice->invoice_number }}">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase text-slate-500">{{ $invoice->customer->name }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-bold text-ivy-navy">{{ $invoice->invoice_number }}</h2>
                <span class="badge {{ $invoice->status === 'overdue' ? 'badge-danger' : (in_array($invoice->status, ['due_today', 'due_soon'], true) ? 'badge-warning' : ($invoice->status === 'paid' ? 'badge-success' : '')) }}">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span>
            </div>
            <dl class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Total</dt><dd class="mt-1 font-bold text-ivy-navy">GHS {{ $invoice->total }}</dd></div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Balance</dt><dd class="mt-1 font-bold text-ivy-navy">GHS {{ $invoice->balance }}</dd></div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Due</dt><dd class="mt-1 font-bold text-ivy-navy">{{ $invoice->due_date->format('d M Y') }}</dd></div>
            </dl>
            <h3 class="mt-8 text-base font-bold text-ivy-navy">Allocations</h3>
            <div class="mt-3 divide-y divide-slate-100">
                @forelse ($invoice->allocations as $allocation)
                    <div class="py-3 text-sm">{{ $allocation->payment->payment_number }} · GHS {{ $allocation->amount }}</div>
                @empty
                    <p class="py-6 text-sm text-slate-500">No payments have been allocated.</p>
                @endforelse
            </div>
        </section>
        <aside class="space-y-5">
            @can('credit.payments')
                <a href="{{ route('credit.payments') }}" class="btn-primary w-full block text-center">Record payment</a>
            @endcan
            @can('credit.reports')
                <a href="{{ route('credit.statement', $invoice->customer) }}" class="btn-secondary w-full block text-center">View customer statement</a>
            @endcan
            @can('credit.reminders')
                <form method="POST" action="{{ route('credit.invoices.reminders', $invoice) }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf
                    <h3 class="text-base font-bold text-ivy-navy">Log reminder</h3>
                    <select class="form-input mt-4" name="reminder_template_id">
                        <option value="">Default local reminder</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn-primary mt-5 w-full" type="submit">Log reminder</button>
                </form>
            @endcan
        </aside>
    </div>
</x-layouts.app>
