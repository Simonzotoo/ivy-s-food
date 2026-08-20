<x-layouts.app title="Expense Report">
    <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        This is an operational expenditure view for internal management. It is not a formal accounting statement.
    </div>
    <form method="GET" class="mb-6 grid gap-3 rounded-md border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_1fr_auto]">
        <input class="form-input mt-0" name="from" type="date" value="{{ request('from', $from->toDateString()) }}">
        <input class="form-input mt-0" name="to" type="date" value="{{ request('to', $to->toDateString()) }}">
        <button class="btn-secondary" type="submit">Apply</button>
    </form>
    <div class="grid gap-4 md:grid-cols-3">
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Approved expenses</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">GHS {{ number_format((float) $total, 2) }}</p>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Records</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $expenses->count() }}</p>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Period</p>
            <p class="mt-2 text-base font-bold text-ivy-navy">{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</p>
        </section>
    </div>
    <section class="mt-6 rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($totalsByCategory as $row)
                <article class="flex items-center justify-between gap-3 p-5">
                    <p class="font-semibold text-ivy-navy">{{ $row['category']->name }}</p>
                    <p class="font-bold text-ivy-navy">GHS {{ number_format((float) $row['total'], 2) }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No approved expenses in this period.</div>
            @endforelse
        </div>
    </section>
</x-layouts.app>
