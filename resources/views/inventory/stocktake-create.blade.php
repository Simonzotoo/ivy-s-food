<x-layouts.app title="Create Stocktake">
    <form method="POST" action="{{ route('inventory.stocktakes.store') }}" class="space-y-6 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="stock_type">Stock type</label>
                <select class="form-input" id="stock_type" name="stock_type" required>
                    <option value="raw">Raw material</option>
                    <option value="finished">Finished product</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="stocktake_date">Stocktake date</label>
                <input class="form-input" id="stocktake_date" name="stocktake_date" type="date" value="{{ now()->toDateString() }}" required>
            </div>
        </div>
        <section>
            <h2 class="text-base font-bold text-ivy-navy">Counted batches</h2>
            <div class="mt-3 space-y-3">
                @for ($i = 0; $i < 6; $i++)
                    <div class="grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1fr_160px]">
                        <select class="form-input mt-0" name="items[{{ $i }}][batch_id]" {{ $i === 0 ? 'required' : '' }}>
                            <option value="">Select batch</option>
                            @foreach ($rawBatches as $batch)
                                <option value="{{ $batch->id }}">Raw: {{ $batch->material->name }} #{{ $batch->id }}</option>
                            @endforeach
                            @foreach ($finishedBatches as $batch)
                                <option value="{{ $batch->id }}">Finished: {{ $batch->productVariant->name }} {{ $batch->batch_number }}</option>
                            @endforeach
                        </select>
                        <input class="form-input mt-0" name="items[{{ $i }}][counted_quantity]" type="number" min="0" step="0.001" placeholder="Counted qty" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                @endfor
            </div>
        </section>
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-input" id="notes" name="notes" rows="3"></textarea>
        <div class="flex justify-end gap-3">
            <a href="{{ route('inventory.stocktakes') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Save stocktake</button>
        </div>
    </form>
</x-layouts.app>
