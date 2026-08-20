<x-layouts.app title="Create Recipe">
    <form method="POST" action="{{ route('recipes.store') }}" class="space-y-6 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="form-label" for="product_variant_id">Product variant</label>
                <select class="form-input" id="product_variant_id" name="product_variant_id" required>
                    <option value="">Select product</option>
                    @foreach ($variants as $variant)
                        <option value="{{ $variant->id }}">{{ $variant->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="expected_output_quantity">Expected output</label>
                <input class="form-input" id="expected_output_quantity" name="expected_output_quantity" type="number" min="0.001" step="0.001" value="{{ old('expected_output_quantity') }}">
            </div>
            <div>
                <label class="form-label" for="output_unit_id">Output unit</label>
                <select class="form-input" id="output_unit_id" name="output_unit_id">
                    <option value="">Not set</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <section>
            <h2 class="text-base font-bold text-ivy-navy">Materials and packaging</h2>
            <div class="mt-3 space-y-3">
                @for ($i = 0; $i < 8; $i++)
                    <div class="grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 lg:grid-cols-[1fr_150px_140px_160px_140px]">
                        <select class="form-input mt-0" name="materials[{{ $i }}][material_id]" {{ $i === 0 ? 'required' : '' }}>
                            <option value="">Material</option>
                            @foreach ($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                            @endforeach
                        </select>
                        <select class="form-input mt-0" name="materials[{{ $i }}][usage_type]">
                            <option value="ingredient">Ingredient</option>
                            <option value="packaging">Packaging</option>
                        </select>
                        <select class="form-input mt-0" name="materials[{{ $i }}][unit_id]" {{ $i === 0 ? 'required' : '' }}>
                            <option value="">Unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                            @endforeach
                        </select>
                        <input class="form-input mt-0" name="materials[{{ $i }}][quantity]" type="number" min="0.001" step="0.001" placeholder="Quantity" {{ $i === 0 ? 'required' : '' }}>
                        <input class="form-input mt-0" name="materials[{{ $i }}][wastage_allowance_percent]" type="number" min="0" max="100" step="0.01" placeholder="Wastage %">
                    </div>
                @endfor
            </div>
        </section>

        <div>
            <label class="form-label" for="notes">Notes</label>
            <textarea class="form-input" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('recipes.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Save draft recipe</button>
        </div>
    </form>
</x-layouts.app>
