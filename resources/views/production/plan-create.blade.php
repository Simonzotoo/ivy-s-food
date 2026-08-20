<x-layouts.app title="Create Production Plan">
    <form method="POST" action="{{ route('production.plans.store') }}" class="space-y-5 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label class="form-label" for="product_variant_id">Product variant</label>
            <select class="form-input" id="product_variant_id" name="product_variant_id" required>
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}">{{ $variant->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="recipe_id">Approved recipe</label>
            <select class="form-input" id="recipe_id" name="recipe_id">
                <option value="">Assign later</option>
                @foreach ($recipes as $recipe)
                    <option value="{{ $recipe->id }}">{{ $recipe->productVariant->name }} v{{ $recipe->version }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="planned_date">Planned date</label>
                <input class="form-input" id="planned_date" name="planned_date" type="date" value="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label class="form-label" for="planned_quantity">Planned quantity</label>
                <input class="form-input" id="planned_quantity" name="planned_quantity" type="number" min="0.001" step="0.001" required>
            </div>
        </div>
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-input" id="notes" name="notes" rows="3"></textarea>
        <div class="flex justify-end gap-3">
            <a href="{{ route('production.plans') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Save plan</button>
        </div>
    </form>
</x-layouts.app>
