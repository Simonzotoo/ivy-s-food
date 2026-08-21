@php $oldMaterials = old('materials', []); @endphp
<x-layouts.app title="Create Recipe">
    <div x-data="recipeForm(@js($materialOptions), @js($oldMaterials))" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('recipes.store') }}" class="space-y-6">
            @csrf
            <p class="text-sm text-slate-500">A draft recipe needs approval before it can be used in production.</p>
            <div class="grid gap-5 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="form-label" for="product_variant_id">Product <span class="text-ivy-red">*</span></label>
                    <select class="form-input" id="product_variant_id" name="product_variant_id" required>
                        <option value="">Select product</option>
                        @foreach ($variants as $variant)
                            <option value="{{ $variant->id }}" @selected((string) old('product_variant_id') === (string) $variant->id)>{{ $variant->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="expected_output_quantity">Expected output</label>
                    <input class="form-input" id="expected_output_quantity" name="expected_output_quantity" type="number" min="0.001" step="0.001" value="{{ old('expected_output_quantity') }}">
                </div>
            </div>
            <div>
                <label class="form-label" for="output_unit_id">Output unit</label>
                <select class="form-input" id="output_unit_id" name="output_unit_id">
                    <option value="">Not set</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((string) old('output_unit_id') === (string) $unit->id)>{{ $unit->symbol }}</option>
                    @endforeach
                </select>
            </div>

            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-ivy-navy">Materials and packaging</h2>
                    <button type="button" class="btn-secondary" @click="addLine()">Add material</button>
                </div>
                <div class="mt-3 space-y-3">
                    <template x-for="(line, index) in lines" :key="line.id">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <div class="grid gap-3 lg:grid-cols-[1fr_130px_110px_130px_120px_auto]">
                                <div class="relative">
                                    <input type="hidden" x-bind:name="'materials['+index+'][material_id]'" x-model="line.materialId">
                                    <input type="text" class="form-input mt-0" x-model="line.query" @focus="line.open = true" @click.outside="line.open = false" placeholder="Search material" autocomplete="off" x-bind:required="index === 0">
                                    <div x-show="line.open && filtered(line).length" x-cloak class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                        <template x-for="option in filtered(line)" :key="option.value">
                                            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="select(line, option)">
                                                <span x-text="option.label"></span>
                                                <span class="block text-xs text-slate-500" x-show="option.sublabel" x-text="option.sublabel"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <select class="form-input mt-0" x-bind:name="'materials['+index+'][usage_type]'" x-model="line.usageType">
                                    <option value="ingredient">Ingredient</option>
                                    <option value="packaging">Packaging</option>
                                </select>
                                <select class="form-input mt-0" x-bind:name="'materials['+index+'][unit_id]'" x-model="line.unitId" x-bind:required="!!line.materialId">
                                    <option value="">Unit</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                                    @endforeach
                                </select>
                                <input class="form-input mt-0" x-model="line.quantity" x-bind:name="'materials['+index+'][quantity]'" type="number" min="0.001" step="0.001" placeholder="Quantity" x-bind:required="!!line.materialId">
                                <input class="form-input mt-0" x-model="line.wastage" x-bind:name="'materials['+index+'][wastage_allowance_percent]'" type="number" min="0" max="100" step="0.01" placeholder="Wastage %">
                                <button type="button" class="text-slate-400 hover:text-ivy-red" x-show="lines.length > 1" @click="removeLine(line.id)" aria-label="Remove material">&times;</button>
                            </div>
                        </div>
                    </template>
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
    </div>
</x-layouts.app>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('recipeForm', (materialOptions, oldMaterials) => ({
            materials: materialOptions,
            lines: (oldMaterials && oldMaterials.length ? oldMaterials : [{ material_id: '', usage_type: 'ingredient', unit_id: '', quantity: '', wastage_allowance_percent: '' }]).map((item, i) => {
                const material = materialOptions.find((m) => String(m.value) === String(item.material_id));

                return { id: i + 1, materialId: item.material_id || '', query: material?.label ?? '', open: false, usageType: item.usage_type || 'ingredient', unitId: item.unit_id || '', quantity: item.quantity || '', wastage: item.wastage_allowance_percent || '' };
            }),
            nextId: (oldMaterials?.length || 1) + 1,
            addLine() {
                this.lines.push({ id: this.nextId++, materialId: '', query: '', open: false, usageType: 'ingredient', unitId: '', quantity: '', wastage: '' });
            },
            removeLine(id) {
                if (this.lines.length > 1) {
                    this.lines = this.lines.filter((line) => line.id !== id);
                }
            },
            filtered(line) {
                const q = (line.query || '').toLowerCase();
                const selectedLabel = this.materials.find((m) => String(m.value) === String(line.materialId))?.label ?? '';
                if (!q || q === selectedLabel.toLowerCase()) {
                    return this.materials.slice(0, 50);
                }

                return this.materials.filter((m) => m.label.toLowerCase().includes(q) || (m.sublabel ?? '').toLowerCase().includes(q)).slice(0, 50);
            },
            select(line, option) {
                line.materialId = option.value;
                line.query = option.label;
                line.open = false;
                if (!line.unitId && option.unit_id) {
                    line.unitId = option.unit_id;
                }
            },
        }));
    });
</script>
@endonce
