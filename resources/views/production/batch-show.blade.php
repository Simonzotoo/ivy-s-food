<x-layouts.app title="{{ $batch->batch_number }}">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-slate-500">{{ $batch->productVariant->name }}</p>
                    <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->batch_number }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $batch->production_date->format('d M Y') }} · {{ ucfirst($batch->status) }}</p>
                </div>
                <p class="text-lg font-bold text-ivy-navy">{{ $batch->actual_output_quantity ?? $batch->planned_quantity }} output</p>
            </div>
            <dl class="mt-6 grid gap-4 md:grid-cols-4">
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Yield</dt>
                    <dd class="mt-1 font-bold text-ivy-navy">{{ $batch->yield_percent ? $batch->yield_percent.'%' : 'Pending' }}</dd>
                </div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Rejected</dt>
                    <dd class="mt-1 font-bold text-ivy-navy">{{ $batch->rejected_quantity }}</dd>
                </div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Batch cost</dt>
                    <dd class="mt-1 font-bold text-ivy-navy">{{ $batch->estimated_batch_cost ? 'GHS '.$batch->estimated_batch_cost : 'Pending' }}</dd>
                </div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Unit cost</dt>
                    <dd class="mt-1 font-bold text-ivy-navy">{{ $batch->estimated_unit_cost ? 'GHS '.$batch->estimated_unit_cost : 'Pending' }}</dd>
                </div>
            </dl>

            <h3 class="mt-8 text-base font-bold text-ivy-navy">Recipe requirements</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Material</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Recipe qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($batch->recipe->materials as $material)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ivy-navy">{{ $material->material->name }}</td>
                                <td class="px-4 py-3">{{ ucfirst($material->usage_type) }}</td>
                                <td class="px-4 py-3">{{ $material->quantity }} {{ $material->unit->symbol }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($batch->usages->isNotEmpty())
                <h3 class="mt-8 text-base font-bold text-ivy-navy">Actual material usage</h3>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Material</th>
                                <th class="px-4 py-3">Batch</th>
                                <th class="px-4 py-3">Quantity</th>
                                <th class="px-4 py-3">Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($batch->usages as $usage)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ivy-navy">{{ $usage->material->name }}</td>
                                    <td class="px-4 py-3">{{ $usage->materialBatch?->lot_number ?: 'No lot' }}</td>
                                    <td class="px-4 py-3">{{ $usage->actual_quantity }} {{ $usage->unit->symbol }}</td>
                                    <td class="px-4 py-3">{{ $usage->line_cost ? 'GHS '.$usage->line_cost : 'Pending' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <aside class="space-y-5">
            @can('production.complete')
                @if ($batch->status === 'draft')
                    <form method="POST" action="{{ route('production.batches.complete', $batch) }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
                        @csrf
                        <h3 class="text-base font-bold text-ivy-navy">Complete batch</h3>
                        <label class="form-label mt-4" for="actual_output_quantity">Accepted output</label>
                        <input class="form-input" id="actual_output_quantity" name="actual_output_quantity" type="number" min="0" step="0.001" required>
                        <label class="form-label mt-4" for="rejected_quantity">Rejected output</label>
                        <input class="form-input" id="rejected_quantity" name="rejected_quantity" type="number" min="0" step="0.001" value="0">
                        <label class="form-label mt-4" for="wastage_quantity">Wastage</label>
                        <input class="form-input" id="wastage_quantity" name="wastage_quantity" type="number" min="0" step="0.001" value="0">
                        <button class="btn-primary mt-5 w-full" type="submit">Complete production</button>
                    </form>
                @endif
            @endcan

            @can('production.reverse')
                @if ($batch->status === 'completed')
                    <form method="POST" action="{{ route('production.batches.reverse', $batch) }}" class="rounded-md border border-red-200 bg-white p-6 shadow-sm">
                        @csrf
                        <h3 class="text-base font-bold text-ivy-navy">Reverse batch</h3>
                        <label class="form-label mt-4" for="reason">Reason</label>
                        <textarea class="form-input" id="reason" name="reason" rows="3" required></textarea>
                        <button class="btn-secondary mt-5 w-full" type="submit">Reverse production</button>
                    </form>
                @endif
            @endcan
        </aside>
    </div>
</x-layouts.app>
