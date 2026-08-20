<x-layouts.app title="Communication Consent">
    <section class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($customers as $customer)
                <article class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="font-semibold text-ivy-navy">{{ $customer->name }}</p>
                        <p class="text-sm text-slate-600">{{ $customer->type?->name ?? 'Unclassified' }} · {{ $customer->communication_consent ? 'Consent recorded' : 'No consent' }}{{ $customer->communication_opted_out_at ? ' · Opted out' : '' }}</p>
                    </div>
                    @if (! $customer->communication_opted_out_at)
                        <form method="POST" action="{{ route('broadcasts.customers.opt-out', $customer) }}" class="flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <input class="form-input mt-0" name="reason" placeholder="Opt-out reason" required>
                            <button class="btn-secondary" type="submit">Opt out</button>
                        </form>
                    @else
                        <p class="text-sm text-slate-500">{{ $customer->communication_opt_out_reason }}</p>
                    @endif
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No customers found.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-5">{{ $customers->links() }}</div>
</x-layouts.app>
