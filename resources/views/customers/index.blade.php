<x-layouts.app title="Customers">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="grid flex-1 gap-3 md:grid-cols-[1fr_220px_160px_auto]">
            <input class="form-input mt-0" name="search" value="{{ request('search') }}" placeholder="Search customer, phone or email">
            <select class="form-input mt-0" name="type">
                <option value="">All customer types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected((string) request('type') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
            <select class="form-input mt-0" name="status">
                <option value="">All statuses</option>
                @foreach (['active', 'inactive', 'suspended'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary" type="submit">Filter</button>
        </form>
        @can('customers.manage')
            <a href="{{ route('customers.create') }}" class="btn-primary">Create customer</a>
        @endcan
    </div>

    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($customers as $customer)
                <article class="p-4">
                    <p class="font-bold text-ivy-navy">{{ $customer->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $customer->type->name }} · {{ $customer->defaultSaleType?->name ?? 'Sale type pending' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $customer->normalized_phone ?: 'Phone pending' }}</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <a href="{{ route('customers.show', $customer) }}" class="btn-secondary">Open</a>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No customers match the current filters.</div>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Pricing</th>
                        <th class="px-4 py-3">Credit</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ivy-navy">{{ $customer->name }}</p>
                                <p class="text-xs text-slate-500">{{ $customer->normalized_phone ?: 'Phone pending' }}{{ $customer->email ? ' · '.$customer->email : '' }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $customer->type->name }}</td>
                            <td class="px-4 py-3">{{ $customer->defaultSaleType?->name ?? 'Pending' }}</td>
                            <td class="px-4 py-3">Limit {{ $customer->credit_limit ? 'GHS '.$customer->credit_limit : 'Pending' }} · Balance GHS {{ $customer->outstanding_balance }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('customers.show', $customer) }}" class="btn-secondary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No customers match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $customers->links() }}</div>
</x-layouts.app>
