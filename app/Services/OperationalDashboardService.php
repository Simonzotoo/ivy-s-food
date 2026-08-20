<?php

namespace App\Services;

use App\Models\CreditInvoice;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\Material;
use App\Models\ProductionBatch;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockAdjustment;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class OperationalDashboardService
{
    public function data(): array
    {
        $today = now()->toDateString();
        $todaySales = Sale::query()->whereDate('sale_date', $today)->where('status', 'completed')->sum('total');
        $todaySalesCount = Sale::query()->whereDate('sale_date', $today)->where('status', 'completed')->count();
        $paymentBreakdown = SalePayment::query()->whereDate('received_at', $today)->get()->groupBy('payment_method')->map->sum('amount');
        $bestSellingProducts = SaleItem::query()->with('productVariant')->get()->groupBy('product_variant_id')->map(fn (Collection $items) => [
            'product' => $items->first()->productVariant,
            'quantity' => $items->sum('quantity'),
            'total' => $items->sum('line_total'),
        ])->sortByDesc('quantity')->take(5);

        return [
            'todaySales' => $todaySales,
            'todaySalesCount' => $todaySalesCount,
            'customerCount' => Customer::query()->count(),
            'paymentBreakdown' => collect(['Cash', 'MoMo', 'Credit'])->mapWithKeys(fn (string $method): array => [
                $method => (float) ($paymentBreakdown[$method] ?? 0),
            ]),
            'todayExpenses' => Expense::query()->whereDate('incurred_on', $today)->where('status', 'approved')->sum('amount'),
            'outstandingCredit' => CreditInvoice::query()->where('balance', '>', 0)->sum('balance'),
            'dueSoonCredit' => CreditInvoice::query()->where('balance', '>', 0)->whereBetween('due_date', [now()->addDay()->toDateString(), now()->addDays(7)->toDateString()])->sum('balance'),
            'dueTodayCredit' => CreditInvoice::query()->where('balance', '>', 0)->whereDate('due_date', $today)->sum('balance'),
            'overdueCredit' => CreditInvoice::query()->where('balance', '>', 0)->whereDate('due_date', '<', $today)->sum('balance'),
            'lowRawMaterials' => $this->lowRawMaterials(),
            'lowFinishedProducts' => $this->lowFinishedProducts(),
            'nearExpiryProducts' => FinishedProductBatch::query()->with('productVariant')->where('quantity_available', '>', 0)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', now()->addDays(14)->toDateString())->limit(8)->get(),
            'expiredProducts' => FinishedProductBatch::query()->with('productVariant')->where('quantity_available', '>', 0)->whereDate('expiry_date', '<', $today)->limit(8)->get(),
            'recentPurchases' => Purchase::query()->with('supplier')->latest('purchase_date')->limit(5)->get(),
            'recentProduction' => ProductionBatch::query()->with('productVariant')->latest('production_date')->limit(5)->get(),
            'recentSales' => Sale::query()->with('customer')->latest('sale_date')->limit(5)->get(),
            'recentAdjustments' => StockAdjustment::query()->latest()->limit(5)->get(),
            'bestSellingProducts' => $bestSellingProducts,
            'averageSaleValue' => $todaySalesCount > 0 ? ((float) $todaySales / $todaySalesCount) : 0,
            'cashSharePercent' => (float) $todaySales > 0 ? (((float) ($paymentBreakdown['Cash'] ?? 0) / (float) $todaySales) * 100) : 0,
            'topSellingProduct' => $bestSellingProducts->first(),
            'stockAlertCount' => $this->lowRawMaterials()->count() + $this->lowFinishedProducts()->count(),
            'salesTrend' => $this->salesTrend(),
            'recentMovements' => FinishedStockMovement::query()->with('productVariant')->latest('occurred_at')->limit(5)->get(),
        ];
    }

    private function salesTrend(): Collection
    {
        $salesByDay = Sale::query()
            ->where('sale_date', '>=', now()->subDays(6)->startOfDay())
            ->where('status', 'completed')
            ->get()
            ->groupBy(fn (Sale $sale): string => $sale->sale_date->toDateString())
            ->map->sum('total');

        return collect(CarbonPeriod::create(now()->subDays(6)->startOfDay(), now()->startOfDay()))
            ->mapWithKeys(fn ($day): array => [
                $day->format('d M') => (float) ($salesByDay[$day->toDateString()] ?? 0),
            ]);
    }

    private function lowRawMaterials(): Collection
    {
        return Material::query()->with('baseUnit')->whereNotNull('reorder_level')->get()
            ->filter(fn (Material $material) => (float) $material->availableQuantity() <= (float) $material->reorder_level)
            ->take(8);
    }

    private function lowFinishedProducts(): Collection
    {
        return ProductVariant::query()->with('finishedBatches')->whereNotNull('reorder_level')->get()
            ->filter(fn (ProductVariant $variant) => (float) $variant->finishedBatches->sum('quantity_available') <= (float) $variant->reorder_level)
            ->take(8);
    }
}
