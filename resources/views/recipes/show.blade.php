<x-layouts.app title="Recipe {{ $recipe->productVariant->name }}">
    <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase text-slate-500">Version {{ $recipe->version }}</p>
                <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $recipe->productVariant->name }}</h2>
                <p class="mt-1 text-sm text-slate-600">Expected output: {{ $recipe->expected_output_quantity ?? 'Not set' }} {{ $recipe->outputUnit?->symbol }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge {{ $recipe->status === 'approved' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($recipe->status) }}</span>
                @can('recipes.approve')
                    @if ($recipe->status !== 'approved')
                        <x-confirm-form
                            action="{{ route('recipes.approve', $recipe) }}"
                            trigger="Approve"
                            message="Approve this recipe? Once approved it can be used to start production."
                            confirm-label="Yes, approve"
                        />
                    @endif
                @endcan
            </div>
        </div>
        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Material</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Quantity</th>
                        <th class="px-4 py-3">Wastage allowance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($recipe->materials as $material)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-ivy-navy">{{ $material->material->name }}</td>
                            <td class="px-4 py-3">{{ ucfirst($material->usage_type) }}</td>
                            <td class="px-4 py-3">{{ $material->quantity }} {{ $material->unit->symbol }}</td>
                            <td class="px-4 py-3">{{ $material->wastage_allowance_percent }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
