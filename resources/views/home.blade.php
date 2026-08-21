<x-layouts.app title="Home">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card">
            <p class="metric-label">Today's Sales</p>
            <p class="metric-value">GHS {{ number_format((float) $todaySales, 2) }}</p>
            <p class="metric-note">{{ $todaySalesCount }} completed sales</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Cash Received</p>
            <p class="metric-value">GHS {{ number_format((float) ($paymentBreakdown['Cash'] ?? 0), 2) }}</p>
            <p class="metric-note">Received today</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">MoMo Received</p>
            <p class="metric-value">GHS {{ number_format((float) ($paymentBreakdown['MoMo'] ?? 0), 2) }}</p>
            <p class="metric-note">Received today</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Credit Sales</p>
            <p class="metric-value">GHS {{ number_format((float) $creditSalesToday, 2) }}</p>
            <p class="metric-note">Given today</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Customers Owing</p>
            <p class="metric-value">GHS {{ number_format((float) $outstandingCredit, 2) }}</p>
            <p class="metric-note">{{ $customersOwingCount }} customers</p>
            @can('credit.view')
                <a href="{{ route('credit.invoices') }}" class="metric-link">View customers owing</a>
            @endcan
        </div>
        <div class="metric-card">
            <p class="metric-label">Low Stock</p>
            <p class="metric-value">{{ $stockAlertCount }}</p>
            <p class="metric-note">Items at or below reorder level</p>
            @can('inventory.view')
                <a href="{{ route('inventory.index') }}" class="metric-link">Check stock</a>
            @endcan
        </div>
        <div class="metric-card">
            <p class="metric-label">Products Expiring Soon</p>
            <p class="metric-value">{{ $nearExpiryCount }}</p>
            <p class="metric-note">Within the next 14 days</p>
            @can('inventory.view')
                <a href="{{ route('inventory.finished-batches') }}" class="metric-link">View batches</a>
            @endcan
        </div>
        <div class="metric-card">
            <p class="metric-label">Today's Expenses</p>
            <p class="metric-value">GHS {{ number_format((float) $todayExpenses, 2) }}</p>
            <p class="metric-note">Approved expenses today</p>
        </div>
    </div>

    <section class="mt-6 calm-panel">
        <h2 class="panel-title">Quick actions</h2>
        <p class="panel-help">Jump straight to the most common daily tasks.</p>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            @can('sales.create')
                <a href="{{ route('sales.pos') }}" class="quick-action">New Sale</a>
            @endcan
            @can('customers.manage')
                <a href="{{ route('customers.create') }}" class="quick-action">Add Customer</a>
            @endcan
            @can('production.start')
                <a href="{{ route('production.batches.create') }}" class="quick-action">Record Production</a>
            @endcan
            @can('purchases.create')
                <a href="{{ route('purchases.create') }}" class="quick-action">Add Purchase</a>
            @endcan
            @can('credit.payments')
                <a href="{{ route('credit.payments') }}" class="quick-action">Receive Credit Payment</a>
            @endcan
            @can('expenses.create')
                <a href="{{ route('expenses.create') }}" class="quick-action">Record Expense</a>
            @endcan
            @can('inventory.view')
                <a href="{{ route('inventory.index') }}" class="quick-action">Check Stock</a>
            @endcan
            @can('imports.view')
                <a href="{{ route('imports.index') }}" class="quick-action">Import Data</a>
            @endcan
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="calm-panel">
            <div class="flex items-center justify-between gap-3">
                <h2 class="panel-title">Low stock</h2>
                @can('inventory.view')
                    <a href="{{ route('inventory.index') }}" class="metric-link">View report</a>
                @endcan
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Raw materials</h3>
                    @forelse ($lowRawMaterials->take(5) as $material)
                        <p class="mt-2 text-sm">{{ $material->name }} · {{ $material->availableQuantity() }} {{ $material->baseUnit?->symbol }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Nothing is low right now.</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Finished products</h3>
                    @forelse ($lowFinishedProducts->take(5) as $variant)
                        <p class="mt-2 text-sm">{{ $variant->name }} · {{ number_format((float) $variant->finishedBatches->sum('quantity_available'), 3) }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Nothing is low right now.</p>
                    @endforelse
                </div>
            </div>
        </section>
        <section class="calm-panel">
            <div class="flex items-center justify-between gap-3">
                <h2 class="panel-title">Expiring stock</h2>
                @can('inventory.view')
                    <a href="{{ route('inventory.finished-batches') }}" class="metric-link">View report</a>
                @endcan
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Expiring soon</h3>
                    @forelse ($nearExpiryProducts->take(5) as $batch)
                        <p class="mt-2 text-sm">{{ $batch->productVariant?->name }} · {{ $batch->expiry_date?->format('d M Y') }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Nothing is expiring soon.</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-500">Already expired</h3>
                    @forelse ($expiredProducts->take(5) as $batch)
                        <p class="mt-2 text-sm">{{ $batch->productVariant?->name }} · {{ $batch->expiry_date?->format('d M Y') }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">No expired finished stock.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    @can('reports.view')
        <section class="mt-6 calm-panel flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="panel-title">Want more detail?</h2>
                <p class="panel-help">Sales trends, payment mix, best-selling products and more live in Reports.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn-secondary">View reports</a>
        </section>
    @endcan
</x-layouts.app>
