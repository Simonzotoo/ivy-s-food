<x-layouts.app title="Create Purchase">
    <form method="POST" action="{{ route('purchases.store') }}" class="space-y-6 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="supplier_id">Supplier</label>
                <select class="form-input" id="supplier_id" name="supplier_id" required>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="purchase_date">Purchase date</label>
                <input class="form-input" id="purchase_date" name="purchase_date" type="date" value="{{ old('purchase_date', now()->toDateString()) }}" required>
            </div>
        </div>
        <div>
            <h2 class="text-base font-bold text-ivy-navy">Items</h2>
            <div class="mt-3 space-y-3">
                @for ($i = 0; $i < 5; $i++)
                    <div class="grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 lg:grid-cols-[1fr_160px_140px_140px]">
                        <select class="form-input mt-0" name="items[{{ $i }}][material_id]" {{ $i === 0 ? 'required' : '' }}>
                            <option value="">Material</option>
                            @foreach ($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                            @endforeach
                        </select>
                        <select class="form-input mt-0" name="items[{{ $i }}][unit_id]" {{ $i === 0 ? 'required' : '' }}>
                            <option value="">Unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                            @endforeach
                        </select>
                        <input class="form-input mt-0" name="items[{{ $i }}][quantity]" type="number" min="0.001" step="0.001" placeholder="Quantity" {{ $i === 0 ? 'required' : '' }}>
                        <input class="form-input mt-0" name="items[{{ $i }}][unit_cost]" type="number" min="0" step="0.0001" placeholder="Unit cost" {{ $i === 0 ? 'required' : '' }}>
                    </div>
                @endfor
            </div>
        </div>
        <div>
            <label class="form-label" for="notes">Notes</label>
            <textarea class="form-input" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
        </div>
        <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
            <h2 class="text-base font-bold text-ivy-navy">Attachment references</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @for ($i = 0; $i < 2; $i++)
                    <input class="form-input mt-0" name="attachments[{{ $i }}][original_name]" value="{{ old("attachments.$i.original_name") }}" placeholder="Document name">
                    <input class="form-input mt-0" name="attachments[{{ $i }}][path]" value="{{ old("attachments.$i.path") }}" placeholder="Stored file reference">
                @endfor
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('purchases.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Save purchase</button>
        </div>
    </form>
</x-layouts.app>
