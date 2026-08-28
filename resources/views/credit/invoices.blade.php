@php
    $statusBadge = fn (string $status) => match ($status) {
        'overdue' => 'badge-danger',
        'due_today', 'due_soon' => 'badge-warning',
        'paid' => 'badge-success',
        default => '',
    };
@endphp
<x-layouts.app title="Customers Owing">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="grid flex-1 gap-3 md:grid-cols-[1fr_180px_auto]">
            <input class="form-input mt-0" name="search" value="{{ request('search') }}" placeholder="Search invoice number or customer">
            <select class="form-input mt-0" name="status">
                <option value="">All statuses</option>
                @foreach (['not_due' => 'Not due', 'due_soon' => 'Due soon', 'due_today' => 'Due today', 'overdue' => 'Overdue', 'partially_paid' => 'Partially paid', 'paid' => 'Paid'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-secondary" type="submit">Filter</button>
        </form>
        @can('credit.payments')
            <a href="{{ route('credit.payments') }}" class="btn-primary">Receive Payment</a>
        @endcan
    </div>
    <div class="mb-5 flex flex-wrap gap-3">
        <a href="{{ route('credit.aging') }}" class="btn-secondary">Aging</a>
        @can('credit.reminders')
            <a href="{{ route('credit.reminders') }}" class="btn-secondary">Reminders</a>
        @endcan
        <x-import-button type="open_credit_balances" label="Import opening balances" />
    </div>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($invoices as $invoice)
                <article class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-bold text-ivy-navy">{{ $invoice->invoice_number }}</p>
                        <p class="text-sm text-slate-600">{{ $invoice->customer->name }} · Due {{ $invoice->due_date->format('d M Y') }}</p>
                        <span class="badge {{ $statusBadge($invoice->status) }}">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="font-bold text-ivy-navy">GHS {{ number_format((float) $invoice->balance, 2) }}</p>
                        <a href="{{ route('credit.invoices.show', $invoice) }}" class="btn-secondary">Open</a>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">
                    @if (request()->hasAny(['search', 'status']))
                        No credit invoices match these filters.
                    @else
                        No customers owe money right now.
                    @endif
                </div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $invoices->links() }}</div>
</x-layouts.app>
