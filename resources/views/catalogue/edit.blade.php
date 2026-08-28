<x-layouts.app title="Edit Product Variant">
    <form method="POST" action="{{ route('catalogue.variants.update', $variant) }}" enctype="multipart/form-data" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="mb-6 flex items-start gap-4">
            @if ($variant->imageUrl())
                <img src="{{ $variant->imageUrl() }}" alt="{{ $variant->name }}" class="h-20 w-20 shrink-0 rounded-md border border-slate-200 object-cover">
            @else
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-md border border-dashed border-slate-300 text-xs text-slate-400">No photo</div>
            @endif
            <div>
                <p class="text-sm font-semibold uppercase text-slate-500">{{ $variant->category->name }}</p>
                <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $variant->name }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $variant->type->name }} · {{ $variant->size->label }}</p>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="sku">Internal SKU</label>
                <input class="form-input" id="sku" name="sku" value="{{ old('sku', $variant->sku) }}">
            </div>
            <div>
                <label class="form-label" for="retail_price">Retail price (GHS)</label>
                <input class="form-input" id="retail_price" name="retail_price" type="number" min="0" step="0.01" value="{{ old('retail_price', $variant->retail_price) }}">
            </div>
            <div>
                <label class="form-label" for="wholesale_price">Wholesale price (GHS)</label>
                <input class="form-input" id="wholesale_price" name="wholesale_price" type="number" min="0" step="0.01" value="{{ old('wholesale_price', $variant->wholesale_price) }}">
            </div>
            <div>
                <label class="form-label" for="distributor_price">Distributor price (GHS)</label>
                <input class="form-input" id="distributor_price" name="distributor_price" type="number" min="0" step="0.01" value="{{ old('distributor_price', $variant->distributor_price) }}">
            </div>
            <div>
                <label class="form-label" for="status">Status</label>
                <select class="form-input" id="status" name="status" required>
                    @foreach (['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $variant->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="reorder_level">Reorder level</label>
                <input class="form-input" id="reorder_level" name="reorder_level" type="number" min="0" step="0.001" value="{{ old('reorder_level', $variant->reorder_level) }}">
            </div>
            <div>
                <label class="form-label" for="shelf_life_days">Shelf life in days</label>
                <input class="form-input" id="shelf_life_days" name="shelf_life_days" type="number" min="1" value="{{ old('shelf_life_days', $variant->shelf_life_days) }}">
            </div>
            <div class="md:col-span-2">
                <label class="form-label" for="image">Product photo</label>
                <input class="form-input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP. Maximum size 5 MB. Uploading a new photo replaces the current one.</p>
                @error('image')
                    <p class="mt-1 text-sm text-ivy-red">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Active variants require SKU plus Retail, Wholesale and Distributor prices. Shelf life and reorder levels remain optional until confirmed.
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('catalogue.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Save variant</button>
        </div>
    </form>
</x-layouts.app>
