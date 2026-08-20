<x-layouts.app title="Purchases">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-ivy-navy">Purchases</h2>
            <p class="mt-1 text-sm text-slate-600">Record materials bought from suppliers, then receive them into stock when they arrive.</p>
        </div>
        @can('purchases.create')
            <a href="{{ route('purchases.create') }}" class="btn-primary">Add purchase</a>
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
                        <th class="px-4 py-3">Amount</th>
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
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <p class="font-semibold text-ivy-navy">No purchases recorded yet.</p>
                                @can('purchases.create')
                                    <a href="{{ route('purchases.create') }}" class="mt-4 btn-primary">Add first purchase</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $purchases->links() }}</div>
    </div>
</x-layouts.app>
