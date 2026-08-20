<x-layouts.app title="Dashboard">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Today's sales</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">GHS {{ number_format((float) $todaySales, 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $todaySalesCount }} completed sales</p>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Customers</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $customerCount }}</p>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Today's expenses</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">GHS {{ number_format((float) $todayExpenses, 2) }}</p>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Outstanding credit</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">GHS {{ number_format((float) $outstandingCredit, 2) }}</p>
        </section>
    </div>

    <section class="mt-6 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-ivy-navy">Analytics</h2>
                <p class="mt-1 text-sm text-slate-500">Quick readings from today’s activity and current stock.</p>
            </div>
            @can('reports.view')
                <a href="{{ route('reports.index') }}" class="btn-secondary">Open reports</a>
            @endcan
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Average sale value</p>
                <p class="mt-2 text-2xl font-bold text-ivy-navy">GHS {{ number_format((float) $averageSaleValue, 2) }}</p>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Cash share today</p>
                <p class="mt-2 text-2xl font-bold text-ivy-navy">{{ number_format((float) $cashSharePercent, 1) }}%</p>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Top product</p>
                @if ($topSellingProduct)
                    <p class="mt-2 text-lg font-bold text-ivy-navy">{{ $topSellingProduct['product']?->name ?? 'Unknown product' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ number_format((float) $topSellingProduct['quantity'], 3) }} sold</p>
                @else
                    <p class="mt-2 text-lg font-bold text-ivy-navy">No sales yet</p>
                @endif
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Stock alerts</p>
                <p class="mt-2 text-2xl font-bold text-ivy-navy">{{ $stockAlertCount }}</p>
                <p class="mt-1 text-sm text-slate-600">Low raw or finished stock</p>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Payment breakdown</h2>
            <div class="mt-4 space-y-3">
                @foreach (['Cash', 'MoMo', 'Credit'] as $method)
                    <div class="flex items-center justify-between text-sm">
                        <span>{{ $method }}</span>
                        <span class="font-bold text-ivy-navy">GHS {{ number_format((float) ($paymentBreakdown[$method] ?? 0), 2) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Credit reminders</h2>
            <dl class="mt-4 grid grid-cols-3 gap-3 text-center text-sm">
                <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-500">Due soon</dt><dd class="font-bold text-ivy-navy">GHS {{ number_format((float) $dueSoonCredit, 2) }}</dd></div>
                <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-500">Today</dt><dd class="font-bold text-ivy-navy">GHS {{ number_format((float) $dueTodayCredit, 2) }}</dd></div>
                <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-500">Overdue</dt><dd class="font-bold text-ivy-navy">GHS {{ number_format((float) $overdueCredit, 2) }}</dd></div>
            </dl>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Sales trend</h2>
            <div class="mt-4 space-y-2">
                @forelse ($salesTrend as $day => $total)
                    <div class="flex items-center justify-between text-sm"><span>{{ $day }}</span><span class="font-bold text-ivy-navy">GHS {{ number_format((float) $total, 2) }}</span></div>
                @empty
                    <p class="text-sm text-slate-500">No completed sales in the last seven days.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Inventory alerts</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Low raw materials</h3>
                    @forelse ($lowRawMaterials as $material)
                        <p class="mt-2 text-sm">{{ $material->name }} · {{ $material->availableQuantity() }} {{ $material->baseUnit?->symbol }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">No low raw-material alerts.</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Low finished stock</h3>
                    @forelse ($lowFinishedProducts as $variant)
                        <p class="mt-2 text-sm">{{ $variant->name }} · {{ number_format((float) $variant->finishedBatches->sum('quantity_available'), 3) }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">No low finished-stock alerts.</p>
                    @endforelse
                </div>
            </div>
        </section>
        <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Expiry controls</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Near expiry</h3>
                    @forelse ($nearExpiryProducts as $batch)
                        <p class="mt-2 text-sm">{{ $batch->productVariant?->name }} · {{ $batch->expiry_date?->format('d M Y') }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">No near-expiry finished stock.</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Expired</h3>
                    @forelse ($expiredProducts as $batch)
                        <p class="mt-2 text-sm">{{ $batch->productVariant?->name }} · {{ $batch->expiry_date?->format('d M Y') }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">No expired finished stock.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        @foreach ([['Recent sales', $recentSales, 'sale_number', 'total'], ['Recent purchases', $recentPurchases, 'purchase_number', 'subtotal'], ['Recent production', $recentProduction, 'batch_number', 'status']] as [$title, $items, $primary, $secondary])
            <section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-ivy-navy">{{ $title }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($items as $item)
                        <p class="text-sm"><span class="font-semibold text-ivy-navy">{{ $item->{$primary} }}</span> · {{ $item->{$secondary} }}</p>
                    @empty
                        <p class="text-sm text-slate-500">No records yet.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <section class="mt-6 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-ivy-navy">Best-selling products</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            @forelse ($bestSellingProducts as $row)
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <p class="font-semibold text-ivy-navy">{{ $row['product']?->name ?? 'Unknown product' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ number_format((float) $row['quantity'], 3) }} sold · GHS {{ number_format((float) $row['total'], 2) }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No product sales yet.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-6 flex flex-col gap-4 rounded-md border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-bold text-ivy-navy">Module 1 foundation</h2>
            <p class="mt-1 text-sm text-slate-600">Authentication, company settings and audit controls remain active beneath the operational modules.</p>
        </div>
        <img src="{{ asset('images/ivys-logo.png') }}" alt="Ivy's Food" class="h-16 w-16 object-contain">
    </section>
</x-layouts.app>
