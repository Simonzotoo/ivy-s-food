<x-layouts.app title="Purchases">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="grid flex-1 gap-3 md:grid-cols-[1fr_160px_auto]">
            <input class="form-input mt-0" name="search" value="{{ request('search') }}" placeholder="Search purchase number or supplier">
            <select class="form-input mt-0" name="status">
                <option value="">All statuses</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="received" @selected(request('status') === 'received')>Received</option>
            </select>
            <button class="btn-secondary" type="submit">Filter</button>
        </form>
        @can('purchases.create')
            <a href="{{ route('purchases.create') }}" class="btn-primary">Add Purchase</a>
        @endcan
    </div>
    <p class="mb-3 text-sm text-slate-500">Purchases stay editable while draft. Receiving creates stock batches and movements.</p>
    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Purchase</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Subtotal</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-ivy-navy">{{ $purchase->purchase_number }}</td>
                            <td class="px-4 py-3">{{ $purchase->supplier->name }}</td>
                            <td class="px-4 py-3">{{ $purchase->purchase_date->format('d M Y') }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $purchase->status === 'received' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($purchase->status) }}</span></td>
                            <td class="px-4 py-3">GHS {{ number_format((float) $purchase->subtotal, 2) }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('purchases.show', $purchase) }}" class="btn-secondary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">
                            @if (request()->hasAny(['search', 'status']))
                                No purchases match these filters.
                            @else
                                No purchases recorded yet. Click "Add Purchase" to record the first one.
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $purchases->links() }}</div>
    </div>
</x-layouts.app>
