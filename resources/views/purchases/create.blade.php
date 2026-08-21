@php $oldItems = old('items', []); @endphp
<x-layouts.app title="Add Purchase">
    <div x-data="purchaseForm(@js($materialOptions), @js($oldItems))" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('purchases.store') }}" class="space-y-6">
            @csrf
            <p class="text-sm text-slate-500">Record what you bought from a supplier. Stock is added separately once you receive the goods.</p>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="supplier_id">Supplier <span class="text-ivy-red">*</span></label>
                    <select class="form-input" id="supplier_id" name="supplier_id" required>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="purchase_date">Purchase date</label>
                    <input class="form-input" id="purchase_date" name="purchase_date" type="date" value="{{ old('purchase_date', now()->toDateString()) }}" required>
                </div>
            </div>

            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-ivy-navy">Items</h2>
                    <button type="button" class="btn-secondary" @click="addLine()">Add item</button>
                </div>
                <div class="mt-3 space-y-3">
                    <template x-for="(line, index) in lines" :key="line.id">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <div class="grid gap-3 lg:grid-cols-[1fr_110px_120px_130px_auto]">
                                <div class="relative">
                                    <input type="hidden" x-bind:name="'items['+index+'][material_id]'" x-model="line.materialId">
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
                                <select class="form-input mt-0" x-bind:name="'items['+index+'][unit_id]'" x-model="line.unitId" x-bind:required="!!line.materialId">
                                    <option value="">Unit</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                                    @endforeach
                                </select>
                                <input class="form-input mt-0" x-model="line.quantity" x-bind:name="'items['+index+'][quantity]'" type="number" min="0.001" step="0.001" placeholder="Quantity" x-bind:required="!!line.materialId">
                                <input class="form-input mt-0" x-model="line.unitCost" x-bind:name="'items['+index+'][unit_cost]'" type="number" min="0" step="0.0001" placeholder="Unit cost" x-bind:required="!!line.materialId">
                                <div class="flex items-center justify-between gap-2 lg:justify-end">
                                    <span class="font-semibold text-ivy-navy lg:hidden">Line total</span>
                                    <span class="font-bold text-ivy-navy" x-text="'GHS ' + lineTotal(line).toFixed(2)"></span>
                                    <button type="button" class="text-slate-400 hover:text-ivy-red" x-show="lines.length > 1" @click="removeLine(line.id)" aria-label="Remove item">&times;</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="mt-3 text-right text-sm font-semibold text-ivy-navy">Total: GHS <span x-text="total().toFixed(2)"></span></p>
            </section>

            <details class="rounded-md border border-slate-200 p-4" {{ old('notes') || old('attachments.0.original_name') ? 'open' : '' }}>
                <summary class="cursor-pointer text-sm font-semibold text-ivy-navy">More details (notes, attachment references)</summary>
                <div class="mt-4">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea class="form-input" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                </div>
                <div class="mt-4">
                    <p class="form-label">Attachment references</p>
                    <p class="panel-help">If a delivery note or invoice is filed elsewhere, note its name and reference here.</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @for ($i = 0; $i < 2; $i++)
                            <input class="form-input mt-0" name="attachments[{{ $i }}][original_name]" value="{{ old("attachments.$i.original_name") }}" placeholder="Document name, e.g. Invoice #123">
                            <input class="form-input mt-0" name="attachments[{{ $i }}][path]" value="{{ old("attachments.$i.path") }}" placeholder="Filing reference">
                        @endfor
                    </div>
                </div>
            </details>

            <div class="flex justify-end gap-3">
                <a href="{{ route('purchases.index') }}" class="btn-secondary">Cancel</a>
                <button class="btn-primary" type="submit">Save purchase</button>
            </div>
        </form>
    </div>
</x-layouts.app>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('purchaseForm', (materialOptions, oldItems) => ({
            materials: materialOptions,
            lines: (oldItems && oldItems.length ? oldItems : [{ material_id: '', unit_id: '', quantity: '', unit_cost: '' }]).map((item, i) => {
                const material = materialOptions.find((m) => String(m.value) === String(item.material_id));

                return { id: i + 1, materialId: item.material_id || '', query: material?.label ?? '', open: false, unitId: item.unit_id || '', quantity: item.quantity || '', unitCost: item.unit_cost || '' };
            }),
            nextId: (oldItems?.length || 1) + 1,
            addLine() {
                this.lines.push({ id: this.nextId++, materialId: '', query: '', open: false, unitId: '', quantity: '', unitCost: '' });
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
            lineTotal(line) {
                const qty = parseFloat(line.quantity) || 0;
                const cost = parseFloat(line.unitCost) || 0;

                return qty * cost;
            },
            total() {
                return this.lines.reduce((sum, line) => sum + this.lineTotal(line), 0);
            },
        }));
    });
</script>
@endonce
