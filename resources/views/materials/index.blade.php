<x-layouts.app title="Materials">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Configure raw materials and packaging materials. Stock is derived from batches and movements.</p>
        <div class="flex flex-wrap gap-2">
            <x-import-button type="raw_materials" label="Import materials" />
            @can('materials.manage')
                <a href="{{ route('materials.create') }}" class="btn-primary">Create material</a>
            @endcan
        </div>
    </div>
    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($materials as $material)
                <article class="p-4">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-ivy-navy">{{ $material->name }}</p>
                        <span class="badge {{ $material->status === 'active' ? 'badge-success' : '' }}">{{ ucfirst($material->status) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">SKU: {{ $material->sku ?: 'Pending' }} · {{ $material->category->name }}</p>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <div>
                            @php($low = $material->reorder_level !== null && (float) $material->availableQuantity() <= (float) $material->reorder_level)
                            <span class="{{ $low ? 'font-semibold text-ivy-red' : '' }}">{{ $material->availableQuantity() }} {{ $material->baseUnit->symbol }} available</span>
                            @if ($low)
                                <span class="badge badge-warning ml-1">Low</span>
                            @endif
                        </div>
                        @can('materials.manage')
                            <a href="{{ route('materials.edit', $material) }}" class="btn-secondary">Edit</a>
                        @endcan
                    </div>
                </article>
            @empty
                <div class="px-4 py-10 text-center text-slate-500">No materials configured yet.</div>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Material</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Available</th>
                        <th class="px-4 py-3">Reorder</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($materials as $material)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-ivy-navy">{{ $material->name }}</p>
                                    <span class="badge {{ $material->status === 'active' ? 'badge-success' : '' }}">{{ ucfirst($material->status) }}</span>
                                </div>
                                <p class="text-xs text-slate-500">SKU: {{ $material->sku ?: 'Pending' }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $material->category->name }}</td>
                            <td class="px-4 py-3">{{ $material->baseUnit->symbol }}</td>
                            <td class="px-4 py-3">
                                @php($low = $material->reorder_level !== null && (float) $material->availableQuantity() <= (float) $material->reorder_level)
                                <span class="{{ $low ? 'font-semibold text-ivy-red' : '' }}">{{ $material->availableQuantity() }}</span>
                                @if ($low)
                                    <span class="badge badge-warning ml-1">Low</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $material->reorder_level ?? 'Pending' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('materials.manage')
                                    <a href="{{ route('materials.edit', $material) }}" class="btn-secondary">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No materials configured yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $materials->links() }}</div>
    </div>
</x-layouts.app>
