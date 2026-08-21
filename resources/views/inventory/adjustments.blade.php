<x-layouts.app title="Adjust Stock">
    <form method="POST" action="{{ route('inventory.adjustments.store') }}" class="mb-6 grid gap-4 rounded-md border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[120px_1fr_180px_140px_1fr_auto]">
        @csrf
        <select class="form-input mt-0" name="stock_type" required>
            <option value="raw">Raw</option>
            <option value="finished">Finished</option>
        </select>
        <select class="form-input mt-0" name="batch_id" required>
            <option value="">Select batch</option>
            @foreach ($rawBatches as $batch)
                <option value="{{ $batch->id }}">Raw: {{ $batch->material->name }} #{{ $batch->id }}</option>
            @endforeach
            @foreach ($finishedBatches as $batch)
                <option value="{{ $batch->id }}">Finished: {{ $batch->productVariant->name }} {{ $batch->batch_number }}</option>
            @endforeach
        </select>
        <select class="form-input mt-0" name="adjustment_type" required>
            @foreach (['increase', 'decrease', 'damage', 'spoilage', 'sample', 'internal_use', 'expired', 'quarantine', 'release_quarantine'] as $type)
                <option value="{{ $type }}">{{ str_replace('_', ' ', ucfirst($type)) }}</option>
            @endforeach
        </select>
        <input class="form-input mt-0" name="quantity" type="number" min="0.001" step="0.001" placeholder="Qty" required>
        <input class="form-input mt-0" name="reason" placeholder="Reason" required>
        <button class="btn-primary" type="submit">Record</button>
    </form>

    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($adjustments as $adjustment)
                <article class="p-4 text-sm">
                    <p class="font-semibold text-ivy-navy">{{ ucfirst($adjustment->stock_type) }} · {{ $adjustment->adjustment_type }} · {{ $adjustment->quantity }}</p>
                    <p class="mt-1 text-slate-600">{{ $adjustment->reason }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No stock adjustments yet.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $adjustments->links() }}</div>
</x-layouts.app>
