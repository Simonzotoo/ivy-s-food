<x-layouts.app title="{{ $customer->name }}">
    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-slate-500">{{ $customer->type->name }}</p>
                    <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $customer->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $customer->normalized_phone ?: 'Phone pending' }}{{ $customer->email ? ' · '.$customer->email : '' }}</p>
                </div>
                @can('customers.manage')
                    <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary">Edit</a>
                @endcan
            </div>
            <dl class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Sale type</dt>
                    <dd class="mt-1 font-bold text-ivy-navy">{{ $customer->defaultSaleType?->name ?? 'Pending' }}</dd>
                </div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Credit limit</dt>
                    <dd class="mt-1 font-bold text-ivy-navy">{{ $customer->credit_limit ? 'GHS '.$customer->credit_limit : 'Pending' }}</dd>
                </div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Outstanding</dt>
                    <dd class="mt-1 font-bold text-ivy-navy">GHS {{ $customer->outstanding_balance }}</dd>
                    @can('credit.reports')
                        <a href="{{ route('credit.statement', $customer) }}" class="metric-link">View statement</a>
                    @endcan
                </div>
            </dl>
            @can('sales.view')
                <section class="mt-8 rounded-md border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-ivy-navy">Recent purchases</h3>
                        <a href="{{ route('sales.index') }}" class="metric-link">View all sales</a>
                    </div>
                    <div class="mt-3 divide-y divide-slate-200">
                        @forelse ($recentSales as $sale)
                            <a href="{{ route('sales.show', $sale) }}" class="flex items-center justify-between gap-3 py-3 text-sm hover:bg-slate-100">
                                <div>
                                    <p class="font-semibold text-ivy-navy">{{ $sale->sale_number }}</p>
                                    <p class="text-slate-500">{{ $sale->sale_date->format('d M Y') }} · {{ $sale->saleType->name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-ivy-navy">GHS {{ number_format((float) $sale->total, 2) }}</p>
                                    <span class="badge {{ $sale->payment_status === 'paid' ? 'badge-success' : ($sale->payment_status === 'unpaid' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($sale->payment_status) }}</span>
                                </div>
                            </a>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">No sales recorded for this customer yet.</p>
                        @endforelse
                    </div>
                </section>
            @endcan
        </section>

        <aside class="space-y-5">
            @can('customers.communications')
                <form method="POST" action="{{ route('customers.communications.store', $customer) }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf
                    <h3 class="text-base font-bold text-ivy-navy">Log communication</h3>
                    <label class="form-label mt-4" for="channel">Channel</label>
                    <select class="form-input" id="channel" name="channel" required>
                        @foreach (['phone', 'sms', 'whatsapp', 'email', 'in_person'] as $channel)
                            <option value="{{ $channel }}">{{ str_replace('_', ' ', ucfirst($channel)) }}</option>
                        @endforeach
                    </select>
                    <label class="form-label mt-4" for="direction">Direction</label>
                    <select class="form-input" id="direction" name="direction" required>
                        <option value="outbound">Outbound</option>
                        <option value="inbound">Inbound</option>
                    </select>
                    <label class="form-label mt-4" for="subject">Subject</label>
                    <input class="form-input" id="subject" name="subject">
                    <label class="form-label mt-4" for="message">Message</label>
                    <textarea class="form-input" id="message" name="message" rows="3"></textarea>
                    <button class="btn-primary mt-5 w-full" type="submit">Log communication</button>
                </form>
            @endcan

            <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-bold text-ivy-navy">Communication history</h3>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse ($customer->communications as $communication)
                        <div class="py-3 text-sm">
                            <p class="font-semibold text-ivy-navy">{{ ucfirst(str_replace('_', ' ', $communication->channel)) }} · {{ ucfirst($communication->direction) }}</p>
                            <p class="text-slate-600">{{ $communication->subject ?: 'No subject' }}</p>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500">No communication has been logged.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</x-layouts.app>
