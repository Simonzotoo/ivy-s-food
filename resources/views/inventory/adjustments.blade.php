@php
    $adjustmentLabels = [
        'increase' => 'Increase (found extra stock)',
        'decrease' => 'Decrease (correct an over-count)',
        'damage' => 'Damage',
        'spoilage' => 'Spoilage',
        'sample' => 'Given as a sample',
        'internal_use' => 'Used internally',
        'expired' => 'Expired',
        'quarantine' => 'Put in quarantine',
        'release_quarantine' => 'Released from quarantine',
    ];
@endphp
<x-layouts.app title="Adjust Stock">
    <div x-data="adjustmentForm(@js($batchOptions))" class="mb-6 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-ivy-navy">Record a stock adjustment</h2>
        <p class="mt-1 text-sm text-slate-500">Use this for anything that changes stock outside a normal sale, purchase or production run - damage, spoilage, samples, or correcting a count.</p>
        <form method="POST" action="{{ route('inventory.adjustments.store') }}" class="mt-4 grid gap-4 lg:grid-cols-[1fr_200px_150px_1fr_auto]">
            @csrf
            <div class="relative">
                <input type="hidden" name="stock_type" x-model="stockType">
                <input type="hidden" name="batch_id" x-model="batchId">
                <input type="text" class="form-input mt-0" x-model="query" @focus="open = true" @click.outside="open = false" placeholder="Search batch" autocomplete="off" required>
                <div x-show="open && filtered().length" x-cloak class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                    <template x-for="option in filtered()" :key="option.stock_type + '-' + option.value">
                        <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="select(option)">
                            <span x-text="option.label"></span>
                            <span class="block text-xs text-slate-500" x-text="option.sublabel"></span>
                        </button>
                    </template>
                </div>
            </div>
            <select class="form-input mt-0" name="adjustment_type" required>
                @foreach ($adjustmentLabels as $type => $label)
                    <option value="{{ $type }}">{{ $label }}</option>
                @endforeach
            </select>
            <input class="form-input mt-0" name="quantity" type="number" min="0.001" step="0.001" placeholder="Quantity" required>
            <input class="form-input mt-0" name="reason" placeholder="Reason" required>
            <button class="btn-primary" type="submit">Record</button>
        </form>
    </div>

    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($adjustments as $adjustment)
                <article class="p-4 text-sm">
                    <p class="font-semibold text-ivy-navy">{{ ucfirst($adjustment->stock_type) }} · {{ $adjustmentLabels[$adjustment->adjustment_type] ?? $adjustment->adjustment_type }} · {{ $adjustment->quantity }}</p>
                    <p class="mt-1 text-slate-600">{{ $adjustment->reason }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No stock adjustments yet.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $adjustments->links() }}</div>
</x-layouts.app>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adjustmentForm', (batchOptions) => ({
            batches: batchOptions,
            query: '',
            open: false,
            stockType: '',
            batchId: '',
            filtered() {
                const q = this.query.toLowerCase();
                const selected = this.batches.find((b) => String(b.value) === String(this.batchId) && b.stock_type === this.stockType);
                if (!q || q === (selected?.label ?? '').toLowerCase()) {
                    return this.batches.slice(0, 50);
                }

                return this.batches.filter((b) => b.label.toLowerCase().includes(q)).slice(0, 50);
            },
            select(option) {
                this.stockType = option.stock_type;
                this.batchId = option.value;
                this.query = option.label;
                this.open = false;
            },
        }));
    });
</script>
@endonce
