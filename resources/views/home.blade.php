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

    @php
        $salesTrendMax = max(1, (float) collect($salesTrend)->max());
        $paymentTotal = max(0, (float) collect($paymentBreakdown)->sum());
        $cashPercent = $paymentTotal > 0 ? (((float) ($paymentBreakdown['Cash'] ?? 0) / $paymentTotal) * 100) : 0;
        $momoPercent = $paymentTotal > 0 ? (((float) ($paymentBreakdown['MoMo'] ?? 0) / $paymentTotal) * 100) : 0;
        $paymentDonut = $paymentTotal > 0
            ? 'conic-gradient(#e30613 0 '.$cashPercent.'%, #1f3a5a '.$cashPercent.'% '.($cashPercent + $momoPercent).'%, #111827 '.($cashPercent + $momoPercent).'% 100%)'
            : '#e2e8f0';
        $productChartMax = max(1, (float) collect($bestSellingProducts)->max('quantity'));
    @endphp

    <section class="mt-6 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-ivy-navy">Analytics</h2>
                <p class="mt-1 text-sm text-slate-500">Charts from actual sales, payments and stock activity.</p>
            </div>
            @can('reports.view')
                <a href="{{ route('reports.index') }}" class="btn-secondary">Open reports</a>
            @endcan
        </div>
        <div class="mt-5 grid gap-5 xl:grid-cols-[1.25fr_0.9fr]">
            <div class="rounded-md bg-slate-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-ivy-navy">Sales trend</h3>
                        <p class="mt-1 text-xs text-slate-500">Last seven days</p>
                    </div>
                    <p class="text-sm font-semibold text-slate-600">Avg GHS {{ number_format((float) $averageSaleValue, 2) }}</p>
                </div>
                <div class="mt-5 flex h-48 items-end gap-2 overflow-hidden">
                    @foreach ($salesTrend as $day => $total)
                        @php($barHeight = max(6, ((float) $total / $salesTrendMax) * 100))
                        <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                            <div class="flex h-36 w-full items-end rounded-md bg-white px-1">
                                <div class="w-full rounded-t-md bg-ivy-red transition" style="height: {{ $barHeight }}%" title="{{ $day }}: GHS {{ number_format((float) $total, 2) }}"></div>
                            </div>
                            <div class="min-h-8 text-center">
                                <p class="truncate text-[11px] font-semibold text-slate-500">{{ $day }}</p>
                                <p class="truncate text-[11px] text-ivy-navy">GHS {{ number_format((float) $total, 0) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-1">
                <div class="rounded-md bg-slate-50 p-4">
                    <h3 class="text-sm font-bold text-ivy-navy">Payment mix today</h3>
                    <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-center">
                        <div class="relative mx-auto h-32 w-32 shrink-0 rounded-full" style="background: {{ $paymentDonut }};">
                            <div class="absolute inset-5 flex items-center justify-center rounded-full bg-slate-50 text-center">
                                <span class="text-sm font-bold text-ivy-navy">GHS {{ number_format($paymentTotal, 0) }}</span>
                            </div>
                        </div>
                        <div class="w-full space-y-2 text-sm">
                            @foreach ([['Cash', 'bg-ivy-red'], ['MoMo', 'bg-ivy-navy'], ['Credit', 'bg-slate-900']] as [$method, $color])
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $color }}"></span>{{ $method }}</span>
                                    <span class="font-semibold text-ivy-navy">GHS {{ number_format((float) ($paymentBreakdown[$method] ?? 0), 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="rounded-md bg-slate-50 p-4">
                    <h3 class="text-sm font-bold text-ivy-navy">Top products</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($bestSellingProducts->take(4) as $row)
                            @php($width = max(5, ((float) $row['quantity'] / $productChartMax) * 100))
                            <div>
                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <span class="truncate font-semibold text-ivy-navy">{{ $row['product']?->name ?? 'Unknown product' }}</span>
                                    <span class="shrink-0 text-slate-500">{{ number_format((float) $row['quantity'], 3) }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-white">
                                    <div class="h-2 rounded-full bg-ivy-navy" style="width: {{ $width }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No product sales yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Average sale value</p>
                <p class="mt-2 text-xl font-bold text-ivy-navy">GHS {{ number_format((float) $averageSaleValue, 2) }}</p>
            </div>
            <div class="rounded-md border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Cash share today</p>
                <p class="mt-2 text-xl font-bold text-ivy-navy">{{ number_format((float) $cashSharePercent, 1) }}%</p>
            </div>
            <div class="rounded-md border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Top product</p>
                <p class="mt-2 text-base font-bold text-ivy-navy">{{ $topSellingProduct ? ($topSellingProduct['product']?->name ?? 'Unknown product') : 'No sales yet' }}</p>
            </div>
            <div class="rounded-md border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Stock alerts</p>
                <p class="mt-2 text-xl font-bold text-ivy-navy">{{ $stockAlertCount }}</p>
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
