<x-layouts.app title="Customer Messages">
    <section class="mb-5 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-bold text-ivy-navy">Customer messages</h2>
        <p class="mt-1 text-sm text-slate-600">Prepare SMS or WhatsApp messages for customers. Development mode only logs messages locally.</p>
    </section>
    <div class="mb-5 flex flex-wrap items-center justify-end gap-2">
        @can('broadcasts.manage_consent')
            <a href="{{ route('broadcasts.consent') }}" class="btn-secondary">Consent</a>
        @endcan
        @can('broadcasts.manage_templates')
            <a href="{{ route('broadcasts.templates') }}" class="btn-secondary">Templates</a>
        @endcan
        @can('broadcasts.create')
            <a href="{{ route('broadcasts.create') }}" class="btn-primary">Send customer message</a>
        @endcan
    </div>
    <section class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($broadcasts as $broadcast)
                <article class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <a href="{{ route('broadcasts.show', $broadcast) }}" class="font-semibold text-ivy-navy hover:text-ivy-red">{{ $broadcast->broadcast_number }}</a>
                        <p class="text-sm text-slate-600">{{ $broadcast->name }} · {{ strtoupper($broadcast->channel) }} · {{ ucfirst($broadcast->status) }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-center text-sm sm:min-w-80">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3"><p class="font-bold text-ivy-navy">{{ $broadcast->total_recipients }}</p><p class="text-xs text-slate-500">Recipients</p></div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3"><p class="font-bold text-ivy-navy">{{ $broadcast->total_logged }}</p><p class="text-xs text-slate-500">Logged</p></div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3"><p class="font-bold text-ivy-navy">{{ $broadcast->total_skipped }}</p><p class="text-xs text-slate-500">Skipped</p></div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No customer messages have been prepared.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-5">{{ $broadcasts->links() }}</div>
</x-layouts.app>
