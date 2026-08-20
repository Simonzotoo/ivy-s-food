<x-layouts.app title="Home">
    <section class="mb-6 rounded-md bg-ivy-navy p-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-red-100">Today at Ivy's Food</p>
                <h2 class="mt-1 text-2xl font-bold">Start the common daily jobs from here.</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-200">Record sales, check stock, receive payments, add purchases and review what needs attention.</p>
            </div>
            <img src="{{ asset('images/ivys-logo.png') }}" alt="Ivy's Food" class="h-16 w-20 rounded-md bg-white p-1 object-contain">
        </div>
    </section>

    <section class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @can('sales.create')
            <a href="{{ route('sales.pos') }}" class="quick-action">New Sale</a>
        @endcan
        @can('production.start')
            <a href="{{ route('production.batches.create') }}" class="quick-action">Record Production</a>
        @endcan
        @can('purchases.create')
            <a href="{{ route('purchases.create') }}" class="quick-action">Add Purchase</a>
        @endcan
        @can('credit.payments')
            <a href="{{ route('credit.payments') }}" class="quick-action">Receive Payment</a>
        @endcan
        @can('expenses.create')
            <a href="{{ route('expenses.create') }}" class="quick-action">Record Expense</a>
        @endcan
        @can('inventory.view')
            <a href="{{ route('inventory.index') }}" class="quick-action">Check Stock</a>
        @endcan
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <section class="metric-card">
            <p class="metric-label">Today's sales</p>
            <p class="metric-value">GHS {{ number_format((float) $todaySales, 2) }}</p>
            <p class="metric-note">{{ $todaySalesCount }} completed sales</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">Cash received</p>
            <p class="metric-value">GHS {{ number_format((float) ($paymentBreakdown['Cash'] ?? 0), 2) }}</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">MoMo received</p>
            <p class="metric-value">GHS {{ number_format((float) ($paymentBreakdown['MoMo'] ?? 0), 2) }}</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">Credit sales</p>
            <p class="metric-value">GHS {{ number_format((float) ($paymentBreakdown['Credit'] ?? 0), 2) }}</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">Customers owing</p>
            <p class="metric-value">GHS {{ number_format((float) $outstandingCredit, 2) }}</p>
            @can('credit.view')
                <a href="{{ route('credit.invoices') }}" class="metric-link">View customers owing</a>
            @endcan
        </section>
        <section class="metric-card">
            <p class="metric-label">Low stock</p>
            <p class="metric-value">{{ $lowRawMaterials->count() + $lowFinishedProducts->count() }}</p>
            <p class="metric-note">Materials and finished products</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">Expiring soon</p>
            <p class="metric-value">{{ $nearExpiryProducts->count() }}</p>
            <p class="metric-note">{{ $expiredProducts->count() }} already expired</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">Today's expenses</p>
            <p class="metric-value">GHS {{ number_format((float) $todayExpenses, 2) }}</p>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="calm-panel xl:col-span-2">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="panel-title">Needs attention</h2>
                    <p class="panel-help">Only urgent stock, expiry and credit items are shown here.</p>
                </div>
                @can('reports.view')
                    <a href="{{ route('reports.index') }}" class="btn-secondary">View reports</a>
                @endcan
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div class="attention-box">
                    <h3>Low raw materials</h3>
                    @forelse ($lowRawMaterials->take(3) as $material)
                        <p>{{ $material->name }} · {{ $material->availableQuantity() }} {{ $material->baseUnit?->symbol }}</p>
                    @empty
                        <p>No low raw-material alerts.</p>
                    @endforelse
                </div>
                <div class="attention-box">
                    <h3>Low finished stock</h3>
                    @forelse ($lowFinishedProducts->take(3) as $variant)
                        <p>{{ $variant->name }} · {{ number_format((float) $variant->finishedBatches->sum('quantity_available'), 3) }}</p>
                    @empty
                        <p>No low finished-stock alerts.</p>
                    @endforelse
                </div>
                <div class="attention-box">
                    <h3>Credit due</h3>
                    <p>Due soon: GHS {{ number_format((float) $dueSoonCredit, 2) }}</p>
                    <p>Due today: GHS {{ number_format((float) $dueTodayCredit, 2) }}</p>
                    <p>Overdue: GHS {{ number_format((float) $overdueCredit, 2) }}</p>
                </div>
            </div>
        </section>

        <section class="calm-panel">
            <h2 class="panel-title">Recent activity</h2>
            <p class="panel-help">A quick check that work is being recorded.</p>
            <div class="mt-4 space-y-4">
                @foreach ([['Sales', $recentSales, 'sale_number'], ['Purchases', $recentPurchases, 'purchase_number'], ['Production', $recentProduction, 'batch_number']] as [$title, $items, $primary])
                    <div>
                        <h3 class="text-sm font-bold text-ivy-navy">{{ $title }}</h3>
                        @forelse ($items->take(2) as $item)
                            <p class="mt-1 text-sm text-slate-600">{{ $item->{$primary} }}</p>
                        @empty
                            <p class="mt-1 text-sm text-slate-500">No records yet.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
