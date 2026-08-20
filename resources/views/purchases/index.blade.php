<x-layouts.app title="Purchases">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Purchases remain editable only while draft. Receiving creates material batches and stock movements.</p>
        @can('purchases.create')
            <a href="{{ route('purchases.create') }}" class="btn-primary">Create purchase</a>
        @endcan
    </div>
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
                            <td class="px-4 py-3">{{ ucfirst($purchase->status) }}</td>
                            <td class="px-4 py-3">GHS {{ $purchase->subtotal }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('purchases.show', $purchase) }}" class="btn-secondary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No purchases recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $purchases->links() }}</div>
    </div>
</x-layouts.app>
