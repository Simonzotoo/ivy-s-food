@php
    $labels = ['not_due' => 'Not due', 'due_soon' => 'Due soon', 'due_today' => 'Due today', 'overdue' => 'Overdue', 'partially_paid' => 'Partially paid'];
    $accent = fn (string $status) => match ($status) {
        'overdue' => 'border-red-200',
        'due_today', 'due_soon' => 'border-amber-200',
        default => 'border-slate-200',
    };
@endphp
<x-layouts.app title="Credit Aging">
    <p class="mb-5 text-sm text-slate-500">How much customers owe, grouped by how soon (or how overdue) each invoice is.</p>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-5">
        @foreach ($labels as $status => $label)
            <section class="rounded-md border {{ $accent($status) }} bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-ivy-navy">{{ $label }}</h2>
                <p class="mt-2 text-2xl font-bold text-ivy-navy">GHS {{ number_format((float) ($invoices[$status] ?? collect())->sum('balance'), 2) }}</p>
                <div class="mt-4 divide-y divide-slate-100 text-sm">
                    @forelse (($invoices[$status] ?? collect()) as $invoice)
                        <a href="{{ route('credit.invoices.show', $invoice) }}" class="block py-2 hover:text-ivy-red">{{ $invoice->customer->name }} · {{ $invoice->invoice_number }}</a>
                    @empty
                        <p class="py-2 text-slate-500">None</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</x-layouts.app>
