@csrf
@if ($material)
    @method('PUT')
@endif
<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="form-label" for="name">Material name</label>
        <input class="form-input" id="name" name="name" value="{{ old('name', $material->name ?? '') }}" required>
    </div>
    <div>
        <label class="form-label" for="sku">Internal SKU</label>
        <input class="form-input" id="sku" name="sku" value="{{ old('sku', $material->sku ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="material_category_id">Category</label>
        <select class="form-input" id="material_category_id" name="material_category_id" required>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('material_category_id', $material->material_category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="base_unit_id">Base unit</label>
        <select class="form-input" id="base_unit_id" name="base_unit_id" required>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) old('base_unit_id', $material->base_unit_id ?? '') === (string) $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="status">Status</label>
        <select class="form-input" id="status" name="status" required>
            @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $material->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="reorder_level">Reorder level</label>
        <input class="form-input" id="reorder_level" name="reorder_level" type="number" min="0" step="0.001" value="{{ old('reorder_level', $material->reorder_level ?? '') }}">
    </div>
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="track_lot" value="1" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red" @checked(old('track_lot', $material->track_lot ?? true))>
        Track lots or batches
    </label>
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="track_expiry" value="1" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red" @checked(old('track_expiry', $material->track_expiry ?? false))>
        Track expiry
    </label>
    <div class="md:col-span-2">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-input" id="notes" name="notes" rows="3">{{ old('notes', $material->notes ?? '') }}</textarea>
    </div>
</div>
<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('materials.index') }}" class="btn-secondary">Cancel</a>
    <button class="btn-primary" type="submit">Save material</button>
</div>
