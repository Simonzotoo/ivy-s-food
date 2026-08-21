<x-layouts.app title="New Stocktake">
    <div x-data="stocktakeForm(@js($rawBatchOptions), @js($finishedBatchOptions))" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('inventory.stocktakes.store') }}" class="space-y-6">
            @csrf
            <p class="text-sm text-slate-500">Count what's physically on the shelf, then compare it against what the system expects. A manager reviews and approves any difference.</p>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="stock_type">What are you counting? <span class="text-ivy-red">*</span></label>
                    <select class="form-input" id="stock_type" name="stock_type" x-model="stockType" @change="resetLines()" required>
                        <option value="raw">Raw materials</option>
                        <option value="finished">Finished products</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="stocktake_date">Stocktake date</label>
                    <input class="form-input" id="stocktake_date" name="stocktake_date" type="date" value="{{ now()->toDateString() }}" required>
                </div>
            </div>
            <section>
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-ivy-navy">Counted batches</h2>
                    <button type="button" class="btn-secondary" @click="addLine()">Add batch</button>
                </div>
                <div class="mt-3 space-y-3">
                    <template x-for="(line, index) in lines" :key="line.id">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <div class="grid gap-3 md:grid-cols-[1fr_160px_auto]">
                                <div class="relative">
                                    <input type="hidden" x-bind:name="'items['+index+'][batch_id]'" x-model="line.batchId">
                                    <input type="text" class="form-input mt-0" x-model="line.query" @focus="line.open = true" @click.outside="line.open = false" placeholder="Search batch" autocomplete="off" x-bind:required="index === 0">
                                    <div x-show="line.open && filtered(line).length" x-cloak class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                        <template x-for="option in filtered(line)" :key="option.value">
                                            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="select(line, option)">
                                                <span x-text="option.label"></span>
                                                <span class="block text-xs text-slate-500" x-text="option.sublabel"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <input class="form-input mt-0" x-model="line.countedQuantity" x-bind:name="'items['+index+'][counted_quantity]'" type="number" min="0" step="0.001" placeholder="Counted qty" x-bind:required="!!line.batchId">
                                <button type="button" class="text-slate-400 hover:text-ivy-red" x-show="lines.length > 1" @click="removeLine(line.id)" aria-label="Remove batch">&times;</button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
            <label class="form-label" for="notes">Notes</label>
            <textarea class="form-input" id="notes" name="notes" rows="3"></textarea>
            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory.stocktakes') }}" class="btn-secondary">Cancel</a>
                <button class="btn-primary" type="submit">Save stocktake</button>
            </div>
        </form>
    </div>
</x-layouts.app>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('stocktakeForm', (rawOptions, finishedOptions) => ({
            stockType: 'raw',
            rawOptions,
            finishedOptions,
            lines: [{ id: 1, batchId: '', query: '', open: false, countedQuantity: '' }],
            nextId: 2,
            options() {
                return this.stockType === 'raw' ? this.rawOptions : this.finishedOptions;
            },
            resetLines() {
                this.nextId = 2;
                this.lines = [{ id: 1, batchId: '', query: '', open: false, countedQuantity: '' }];
            },
            addLine() {
                this.lines.push({ id: this.nextId++, batchId: '', query: '', open: false, countedQuantity: '' });
            },
            removeLine(id) {
                if (this.lines.length > 1) {
                    this.lines = this.lines.filter((line) => line.id !== id);
                }
            },
            filtered(line) {
                const q = (line.query || '').toLowerCase();
                const options = this.options();
                const selectedLabel = options.find((o) => String(o.value) === String(line.batchId))?.label ?? '';
                if (!q || q === selectedLabel.toLowerCase()) {
                    return options.slice(0, 50);
                }

                return options.filter((o) => o.label.toLowerCase().includes(q)).slice(0, 50);
            },
            select(line, option) {
                line.batchId = option.value;
                line.query = option.label;
                line.open = false;
            },
        }));
    });
</script>
@endonce
