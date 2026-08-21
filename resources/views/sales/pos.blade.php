@php
    $canDiscount = auth()->user()->can('sales.discount');
    $oldItems = old('items', []);
@endphp
<x-layouts.app title="New Sale">
    <div
        x-data="posForm(@js($variantOptions), @js($saleTypes), {{ $canDiscount ? 'true' : 'false' }}, @js($oldItems), @js(old('sale_type_id')), @js(old('payments.0.amount')), @js(old('payments.1.amount')))"
        class="grid gap-6 lg:grid-cols-[1fr_340px]"
    >
        <form method="POST" action="{{ route('sales.store') }}" class="space-y-5 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <p class="text-sm text-slate-500">Pick a sale type, add products, then take payment. Prices update automatically as you go.</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="sale_type_id">Sale type <span class="text-ivy-red">*</span></label>
                    <select class="form-input" id="sale_type_id" name="sale_type_id" x-model="saleTypeId" required>
                        @foreach ($saleTypes as $saleType)
                            <option value="{{ $saleType->id }}">{{ $saleType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="customer_search">Customer</label>
                    <x-searchable-select
                        name="customer_id"
                        :options="$customerOptions"
                        :selected="old('customer_id')"
                        placeholder="Walk-in customer (search to select one)"
                        empty-label="No matching customer."
                    />
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
                            <div class="grid gap-3 lg:grid-cols-[1fr_110px_110px_110px_auto]">
                                <div class="relative">
                                    <input type="hidden" :name="'items['+index+'][product_variant_id]'" x-model="line.productId">
                                    <input
                                        type="text"
                                        class="form-input mt-0"
                                        x-model="line.query"
                                        @focus="line.open = true"
                                        @click.outside="line.open = false"
                                        placeholder="Search product or scan barcode"
                                        autocomplete="off"
                                        x-bind:required="index === 0 && !line.productId"
                                    >
                                    <div x-show="line.open && filteredVariants(line).length" x-cloak class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                        <template x-for="option in filteredVariants(line)" :key="option.value">
                                            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="selectVariant(line, option)">
                                                <span x-text="option.label"></span>
                                                <span class="block text-xs text-slate-500" x-show="option.sublabel" x-text="option.sublabel"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <input class="form-input mt-0" x-model="line.quantity" x-bind:name="'items['+index+'][quantity]'" type="number" min="0.001" step="0.001" placeholder="Qty" x-bind:required="!!line.productId">
                                <div class="form-input mt-0 flex items-center justify-end bg-white font-semibold text-ivy-navy" x-text="unitPrice(line) === null ? '—' : ('GHS ' + fmt(unitPrice(line)))"></div>
                                @if ($canDiscount)
                                    <input class="form-input mt-0" x-model="line.discount" x-bind:name="'items['+index+'][discount_amount]'" type="number" min="0" step="0.01" placeholder="Discount">
                                @else
                                    <div></div>
                                @endif
                                <div class="flex items-center justify-between gap-2 lg:justify-end">
                                    <span class="font-semibold text-ivy-navy lg:hidden">Line total</span>
                                    <span class="font-bold text-ivy-navy" x-text="'GHS ' + fmt(lineTotal(line))"></span>
                                    <button type="button" class="text-slate-400 hover:text-ivy-red" x-show="lines.length > 1" @click="removeLine(line.id)" aria-label="Remove item">&times;</button>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-amber-700" x-show="line.productId && unitPrice(line) === null">This product has no price set for the selected sale type.</p>
                        </div>
                    </template>
                </div>
            </section>

            <section class="rounded-md border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-base font-bold text-ivy-navy">Payment</h2>
                <p class="mt-1 text-sm text-slate-500">Split between cash, MoMo and credit if needed. Any amount left unpaid becomes a credit sale for the selected customer.</p>
                <div class="mt-3 space-y-3">
                    <div class="grid gap-3 md:grid-cols-[160px_1fr_1fr]">
                        <p class="py-2 font-semibold text-ivy-navy">Cash</p>
                        <input type="hidden" name="payments[0][payment_method]" value="Cash">
                        <input class="form-input mt-0" name="payments[0][amount]" x-model="payments.Cash" type="number" min="0" step="0.01" placeholder="Amount">
                        <input class="form-input mt-0" name="payments[0][transaction_reference]" value="{{ old('payments.0.transaction_reference') }}" placeholder="Reference (optional)">
                    </div>
                    <div class="grid gap-3 md:grid-cols-[160px_1fr_1fr]">
                        <p class="py-2 font-semibold text-ivy-navy">MoMo</p>
                        <input type="hidden" name="payments[1][payment_method]" value="MoMo">
                        <input class="form-input mt-0" name="payments[1][amount]" x-model="payments.MoMo" type="number" min="0" step="0.01" placeholder="Amount">
                        <input class="form-input mt-0" name="payments[1][transaction_reference]" value="{{ old('payments.1.transaction_reference') }}" placeholder="MoMo reference">
                    </div>
                    <template x-if="balance() > 0">
                        <div>
                            <input type="hidden" name="payments[2][payment_method]" value="Credit">
                            <input type="hidden" name="payments[2][amount]" x-bind:value="balance()">
                        </div>
                    </template>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-200 pt-4 sm:grid-cols-4">
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Subtotal</p><p class="font-bold text-ivy-navy" x-text="'GHS ' + fmt(subtotal())"></p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Discount</p><p class="font-bold text-ivy-navy" x-text="'GHS ' + fmt(discountTotal())"></p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Total</p><p class="text-lg font-bold text-ivy-navy" x-text="'GHS ' + fmt(total())"></p></div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Balance to credit</p>
                        <p class="text-lg font-bold" :class="balance() > 0 ? 'text-ivy-red' : 'text-ivy-navy'" x-text="'GHS ' + fmt(balance())"></p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-amber-700" x-show="balance() > 0">A customer must be selected above for the remaining balance to go on credit.</p>
            </section>

            <details class="rounded-md border border-slate-200 p-4" {{ old('notes') ? 'open' : '' }}>
                <summary class="cursor-pointer text-sm font-semibold text-ivy-navy">More details (sale date, notes)</summary>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="sale_date">Sale date</label>
                        <input class="form-input" id="sale_date" name="sale_date" type="datetime-local" value="{{ old('sale_date', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div>
                        <label class="form-label" for="notes">Notes</label>
                        <input class="form-input" id="notes" name="notes" value="{{ old('notes') }}">
                    </div>
                </div>
            </details>

            <div class="flex justify-end gap-3">
                <a href="{{ route('sales.index') }}" class="btn-secondary">Cancel</a>
                <button class="btn-primary" type="submit">Complete sale</button>
            </div>
        </form>

        <aside class="h-fit rounded-md border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-20">
            <h2 class="text-base font-bold text-ivy-navy">Sale summary</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Items</dt><dd class="font-semibold text-ivy-navy" x-text="lines.filter(l => l.productId).length"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-semibold text-ivy-navy" x-text="'GHS ' + fmt(subtotal())"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="font-semibold text-ivy-navy" x-text="'GHS ' + fmt(discountTotal())"></dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-3 text-base"><dt class="font-bold text-ivy-navy">Total due</dt><dd class="font-bold text-ivy-navy" x-text="'GHS ' + fmt(total())"></dd></div>
            </dl>
        </aside>
    </div>
</x-layouts.app>

@once
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('posForm', (variantOptions, saleTypes, canDiscount, oldItems, oldSaleTypeId, oldCash, oldMoMo) => ({
            saleTypeId: oldSaleTypeId || (saleTypes.find((s) => s.slug === 'retail') ?? saleTypes[0])?.id || '',
            variants: variantOptions,
            saleTypes: saleTypes,
            canDiscount: canDiscount,
            lines: (oldItems && oldItems.length ? oldItems : [{ product_variant_id: '', quantity: '', discount_amount: '' }]).map((item, i) => {
                const variant = variantOptions.find((v) => String(v.value) === String(item.product_variant_id));

                return { id: i + 1, productId: item.product_variant_id || '', query: variant?.label ?? '', open: false, quantity: item.quantity || '', discount: item.discount_amount || '' };
            }),
            nextId: (oldItems?.length || 1) + 1,
            payments: { Cash: oldCash || '', MoMo: oldMoMo || '' },

            get saleTypeSlug() {
                return this.saleTypes.find((s) => String(s.id) === String(this.saleTypeId))?.slug ?? 'retail';
            },
            priceField() {
                return this.saleTypeSlug === 'wholesale' ? 'wholesale' : (this.saleTypeSlug === 'distributor' ? 'distributor' : 'retail');
            },
            variantById(id) {
                return this.variants.find((v) => String(v.value) === String(id));
            },
            unitPrice(line) {
                const variant = this.variantById(line.productId);
                if (!variant) return null;
                const price = variant[this.priceField()];

                return price === null || price === undefined ? null : parseFloat(price);
            },
            lineTotal(line) {
                const price = this.unitPrice(line);
                if (price === null || !line.quantity) return 0;

                return Math.max(0, (price * parseFloat(line.quantity || 0)) - (parseFloat(line.discount) || 0));
            },
            subtotal() {
                return this.lines.reduce((sum, line) => {
                    const price = this.unitPrice(line);

                    return sum + (price !== null && line.quantity ? price * parseFloat(line.quantity) : 0);
                }, 0);
            },
            discountTotal() {
                return this.lines.reduce((sum, line) => sum + (parseFloat(line.discount) || 0), 0);
            },
            total() {
                return Math.max(0, this.subtotal() - this.discountTotal());
            },
            paidTotal() {
                return (parseFloat(this.payments.Cash) || 0) + (parseFloat(this.payments.MoMo) || 0);
            },
            balance() {
                return Math.max(0, Math.round((this.total() - this.paidTotal()) * 100) / 100);
            },
            addLine() {
                this.lines.push({ id: this.nextId++, productId: '', query: '', open: false, quantity: '', discount: '' });
            },
            removeLine(id) {
                if (this.lines.length > 1) {
                    this.lines = this.lines.filter((line) => line.id !== id);
                }
            },
            filteredVariants(line) {
                const q = (line.query || '').toLowerCase();
                const selectedLabel = this.variantById(line.productId)?.label ?? '';
                if (!q || q === selectedLabel.toLowerCase()) {
                    return this.variants.slice(0, 50);
                }

                return this.variants.filter((v) => v.label.toLowerCase().includes(q)
                    || (v.sublabel ?? '').toLowerCase().includes(q)).slice(0, 50);
            },
            selectVariant(line, option) {
                line.productId = option.value;
                line.query = option.label;
                line.open = false;
                if (!line.quantity) {
                    line.quantity = '1';
                }
            },
            fmt(value) {
                return (Math.round((value + Number.EPSILON) * 100) / 100).toFixed(2);
            },
        }));
    });
</script>
@endonce
