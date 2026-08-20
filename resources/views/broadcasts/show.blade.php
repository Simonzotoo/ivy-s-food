<x-layouts.app title="Broadcast Detail">
    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-slate-500">{{ strtoupper($broadcast->channel) }} · {{ ucfirst($broadcast->status) }}</p>
                    <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $broadcast->broadcast_number }}</h2>
                    <p class="mt-2 font-semibold text-slate-700">{{ $broadcast->name }}</p>
                </div>
                @can('broadcasts.send')
                    @if (in_array($broadcast->status, ['draft', 'scheduled'], true))
                        <form method="POST" action="{{ route('broadcasts.send', $broadcast) }}">
                            @csrf
                            <button class="btn-primary" type="submit">Log now</button>
                        </form>
                    @endif
                @endcan
            </div>
            <div class="mt-6 rounded-md border border-slate-200 bg-slate-50 p-4">
                <p class="font-semibold text-ivy-navy">{{ $broadcast->subject ?: 'No subject' }}</p>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $broadcast->body }}</p>
            </div>
            <h3 class="mt-8 text-base font-bold text-ivy-navy">Recipients</h3>
            <div class="mt-3 divide-y divide-slate-100 rounded-md border border-slate-200">
                @foreach ($broadcast->recipients as $recipient)
                    <article class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-ivy-navy">{{ $recipient->customer->name }}</p>
                            <p class="text-sm text-slate-500">{{ $recipient->customer->type?->name ?? 'Unclassified' }}</p>
                        </div>
                        <p class="text-sm text-slate-600">{{ ucfirst($recipient->status) }}{{ $recipient->skip_reason ? ' · '.$recipient->skip_reason : '' }}</p>
                    </article>
                @endforeach
            </div>
        </section>
        <aside class="space-y-4">
            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-bold text-ivy-navy">Delivery summary</h3>
                <dl class="mt-3 grid grid-cols-3 gap-3 text-center text-sm">
                    <div><dt class="text-slate-500">Recipients</dt><dd class="font-bold text-ivy-navy">{{ $broadcast->total_recipients }}</dd></div>
                    <div><dt class="text-slate-500">Logged</dt><dd class="font-bold text-ivy-navy">{{ $broadcast->total_logged }}</dd></div>
                    <div><dt class="text-slate-500">Skipped</dt><dd class="font-bold text-ivy-navy">{{ $broadcast->total_skipped }}</dd></div>
                </dl>
            </section>
            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-bold text-ivy-navy">Filters</h3>
                <pre class="mt-3 whitespace-pre-wrap rounded-md bg-slate-50 p-3 text-xs text-slate-600">{{ json_encode($broadcast->filters, JSON_PRETTY_PRINT) }}</pre>
            </section>
        </aside>
    </div>
</x-layouts.app>
