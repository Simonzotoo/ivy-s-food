<x-layouts.app title="Suppliers">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Supplier records are configurable; no supplier data is seeded by default.</p>
        @can('suppliers.manage')
            <a href="{{ route('suppliers.create') }}" class="btn-primary">Create supplier</a>
        @endcan
    </div>
    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($suppliers as $supplier)
                <article class="p-4">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold text-ivy-navy">{{ $supplier->name }}</p>
                        <span class="badge {{ $supplier->status === 'active' ? 'badge-success' : '' }}">{{ ucfirst($supplier->status) }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ $supplier->phone ?: 'Phone pending' }}{{ $supplier->email ? ' · '.$supplier->email : '' }}</p>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <p class="text-slate-500">{{ $supplier->purchases_count }} purchase(s)</p>
                        @can('suppliers.manage')
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-secondary">Edit</a>
                        @endcan
                    </div>
                </article>
            @empty
                <div class="px-4 py-10 text-center text-slate-500">No suppliers configured yet.</div>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Purchases</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-ivy-navy">{{ $supplier->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $supplier->phone ?: 'Phone pending' }}{{ $supplier->email ? ' · '.$supplier->email : '' }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $supplier->status === 'active' ? 'badge-success' : '' }}">{{ ucfirst($supplier->status) }}</span></td>
                            <td class="px-4 py-3">{{ $supplier->purchases_count }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('suppliers.manage')
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-secondary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No suppliers configured yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $suppliers->links() }}</div>
    </div>
</x-layouts.app>
