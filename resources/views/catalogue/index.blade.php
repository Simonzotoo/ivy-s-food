<x-layouts.app title="Products and Sizes">
    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Categories</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $categories->count() }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Draft variants</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $statusCounts['draft'] ?? 0 }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Active variants</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $statusCounts['active'] ?? 0 }}</p>
        </div>
    </div>

    <section class="mb-5 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-ivy-navy">Confirmed catalogue baseline</h2>
        <div class="mt-4 grid gap-3 lg:grid-cols-3">
            @foreach ($categories as $category)
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <p class="font-semibold text-ivy-navy">{{ $category->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $category->types_count }} official types · {{ $category->sizes_count }} sizes</p>
                </div>
            @endforeach
        </div>
    </section>

    <form method="GET" class="mb-5 grid gap-3 rounded-md border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_220px_180px_auto_auto]">
        <input class="form-input mt-0" name="search" value="{{ request('search') }}" placeholder="Search variant or SKU">
        <select class="form-input mt-0" name="category">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select class="form-input mt-0" name="status">
            <option value="">All statuses</option>
            @foreach (['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn-secondary" type="submit">Filter</button>
        @can('catalogue.export')
            <a class="btn-primary" href="{{ route('catalogue.export') }}">Export</a>
        @endcan
    </form>

    <div class="mb-5 flex flex-wrap items-center gap-3 text-sm">
        <span class="font-semibold text-slate-500">Bulk import:</span>
        <x-import-button type="product_variants" label="Product updates" />
        <x-import-button type="prices" label="Prices" />
    </div>

    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($variants as $variant)
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            @if ($variant->imageUrl())
                                <img src="{{ $variant->imageUrl() }}" alt="{{ $variant->name }}" class="h-12 w-12 shrink-0 rounded-md border border-slate-200 object-cover">
                            @else
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-dashed border-slate-300 text-[10px] text-slate-400">No photo</div>
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold text-ivy-navy">{{ $variant->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $variant->category->name }} · {{ $variant->type->name }} · {{ $variant->size->label }}</p>
                            </div>
                        </div>
                        <span class="badge shrink-0 {{ $variant->status === 'active' ? 'badge-success' : ($variant->status === 'inactive' ? '' : 'badge-warning') }}">{{ ucfirst($variant->status) }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">SKU</dt>
                            <dd class="mt-1 text-ivy-black">{{ $variant->sku ?: 'Pending' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">Retail</dt>
                            <dd class="mt-1 text-ivy-black">{{ $variant->retail_price ? 'GHS '.$variant->retail_price : 'Pending' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">Wholesale</dt>
                            <dd class="mt-1 text-ivy-black">{{ $variant->wholesale_price ? 'GHS '.$variant->wholesale_price : 'Pending' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">Distributor</dt>
                            <dd class="mt-1 text-ivy-black">{{ $variant->distributor_price ? 'GHS '.$variant->distributor_price : 'Pending' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">Shelf life</dt>
                            <dd class="mt-1 text-ivy-black">{{ $variant->shelf_life_days ? $variant->shelf_life_days.' days' : 'Pending' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 flex flex-wrap justify-end gap-2">
                        @can('catalogue.edit')
                            <a href="{{ route('catalogue.variants.edit', $variant) }}" class="btn-secondary">Edit</a>
                        @endcan
                        @can('catalogue.activate')
                            <x-confirm-form
                                action="{{ route('catalogue.variants.status', $variant) }}"
                                button-class="btn-secondary"
                                trigger="{{ $variant->status === 'active' ? 'Deactivate' : 'Activate' }}"
                                message="{{ $variant->status === 'active' ? $variant->name.' will no longer be available for sale.' : 'Make '.$variant->name.' available for sale again?' }}"
                                confirm-label="Yes, {{ strtolower($variant->status === 'active' ? 'deactivate' : 'activate') }}"
                            >
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $variant->status === 'active' ? 'inactive' : 'active' }}">
                            </x-confirm-form>
                        @endcan
                    </div>
                </article>
            @empty
                <div class="px-4 py-10 text-center text-slate-500">No product variants match the current filters.</div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Variant</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Prices</th>
                        <th class="px-4 py-3">Controls</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($variants as $variant)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-start gap-3">
                                    @if ($variant->imageUrl())
                                        <img src="{{ $variant->imageUrl() }}" alt="{{ $variant->name }}" class="h-12 w-12 shrink-0 rounded-md border border-slate-200 object-cover">
                                    @else
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-dashed border-slate-300 text-[10px] text-slate-400">No photo</div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-ivy-navy">{{ $variant->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $variant->category->name }} · {{ $variant->type->name }} · {{ $variant->size->label }}</p>
                                        <span class="badge mt-2 inline-flex {{ $variant->status === 'active' ? 'badge-success' : ($variant->status === 'inactive' ? '' : 'badge-warning') }}">{{ ucfirst($variant->status) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <p>SKU: {{ $variant->sku ?: 'Pending' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <p>Retail: {{ $variant->retail_price ? 'GHS '.$variant->retail_price : 'Pending' }}</p>
                                <p>Wholesale: {{ $variant->wholesale_price ? 'GHS '.$variant->wholesale_price : 'Pending' }}</p>
                                <p>Distributor: {{ $variant->distributor_price ? 'GHS '.$variant->distributor_price : 'Pending' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <p>Reorder: {{ $variant->reorder_level ?? 'Pending' }}</p>
                                <p>Shelf life: {{ $variant->shelf_life_days ? $variant->shelf_life_days.' days' : 'Pending' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @can('catalogue.edit')
                                        <a href="{{ route('catalogue.variants.edit', $variant) }}" class="btn-secondary">Edit</a>
                                    @endcan
                                    @can('catalogue.activate')
                                        <x-confirm-form
                                            action="{{ route('catalogue.variants.status', $variant) }}"
                                            button-class="btn-secondary"
                                            trigger="{{ $variant->status === 'active' ? 'Deactivate' : 'Activate' }}"
                                            message="{{ $variant->status === 'active' ? $variant->name.' will no longer be available for sale.' : 'Make '.$variant->name.' available for sale again?' }}"
                                            confirm-label="Yes, {{ strtolower($variant->status === 'active' ? 'deactivate' : 'activate') }}"
                                        >
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $variant->status === 'active' ? 'inactive' : 'active' }}">
                                        </x-confirm-form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No product variants match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $variants->links() }}</div>
    </div>
</x-layouts.app>
