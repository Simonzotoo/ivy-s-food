<x-layouts.app title="Point of Sale">
    <form method="GET" class="mb-5 grid gap-3 rounded-md border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_auto]">
        <input class="form-input mt-0" name="search" value="{{ request('search') }}" placeholder="Scan barcode or search product">
        <button class="btn-secondary" type="submit">Search</button>
    </form>

    <form method="POST" action="{{ route('sales.store') }}" class="space-y-6 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 md:grid-cols-3">
            <div>
                <label class="form-label" for="customer_id">Customer</label>
                <select class="form-input" id="customer_id" name="customer_id">
                    <option value="">Walk-in customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="sale_type_id">Sale type</label>
                <select class="form-input" id="sale_type_id" name="sale_type_id" required>
                    @foreach ($saleTypes as $saleType)
                        <option value="{{ $saleType->id }}">{{ $saleType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="sale_date">Sale date</label>
                <input class="form-input" id="sale_date" name="sale_date" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}">
            </div>
        </div>

        <section>
            <h2 class="text-base font-bold text-ivy-navy">Items</h2>
            <div class="mt-3 space-y-3">
                @for ($i = 0; $i < 6; $i++)
                    <div class="grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 lg:grid-cols-[1fr_150px_160px]">
                        <select class="form-input mt-0" name="items[{{ $i }}][product_variant_id]" {{ $i === 0 ? 'required' : '' }}>
                            <option value="">Product</option>
                            @foreach ($variants as $variant)
                                <option value="{{ $variant->id }}">{{ $variant->name }}{{ $variant->barcode ? ' · '.$variant->barcode : '' }}</option>
                            @endforeach
                        </select>
                        <input class="form-input mt-0" name="items[{{ $i }}][quantity]" type="number" min="0.001" step="0.001" placeholder="Qty" {{ $i === 0 ? 'required' : '' }}>
                        @can('sales.discount')
                            <input class="form-input mt-0" name="items[{{ $i }}][discount_amount]" type="number" min="0" step="0.01" placeholder="Discount">
                        @endcan
                    </div>
                @endfor
            </div>
        </section>

        <section class="rounded-md border border-slate-200 bg-slate-50 p-4">
            <h2 class="text-base font-bold text-ivy-navy">Payments</h2>
            <div class="mt-3 space-y-3">
                @foreach (['Cash', 'MoMo', 'Credit'] as $i => $method)
                    <div class="grid gap-3 md:grid-cols-[160px_160px_1fr]">
                        <input type="hidden" name="payments[{{ $i }}][payment_method]" value="{{ $method }}">
                        <p class="py-2 font-semibold text-ivy-navy">{{ $method }}</p>
                        <input class="form-input mt-0" name="payments[{{ $i }}][amount]" type="number" min="0" step="0.01" placeholder="Amount">
                        <input class="form-input mt-0" name="payments[{{ $i }}][transaction_reference]" placeholder="{{ $method === 'MoMo' ? 'MoMo reference' : 'Reference optional' }}">
                    </div>
                @endforeach
            </div>
        </section>

        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-input" id="notes" name="notes" rows="3"></textarea>
        <div class="flex justify-end gap-3">
            <a href="{{ route('sales.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Complete sale</button>
        </div>
    </form>
</x-layouts.app>
