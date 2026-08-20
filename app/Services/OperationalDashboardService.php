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
use Illuminate\Support\Collection;

class OperationalDashboardService
{
    public function data(): array
    {
        $today = now()->toDateString();

        return [
            'todaySales' => Sale::query()->whereDate('sale_date', $today)->where('status', 'completed')->sum('total'),
            'todaySalesCount' => Sale::query()->whereDate('sale_date', $today)->where('status', 'completed')->count(),
            'customerCount' => Customer::query()->count(),
            'paymentBreakdown' => SalePayment::query()->whereDate('received_at', $today)->get()->groupBy('payment_method')->map->sum('amount'),
            'todayCreditSales' => CreditInvoice::query()->whereDate('issue_date', $today)->sum('total'),
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
            'bestSellingProducts' => SaleItem::query()->with('productVariant')->get()->groupBy('product_variant_id')->map(fn (Collection $items) => [
                'product' => $items->first()->productVariant,
                'quantity' => $items->sum('quantity'),
                'total' => $items->sum('line_total'),
            ])->sortByDesc('quantity')->take(5),
            'salesTrend' => Sale::query()->where('sale_date', '>=', now()->subDays(6)->startOfDay())->where('status', 'completed')->get()->groupBy(fn (Sale $sale) => $sale->sale_date->format('d M'))->map->sum('total'),
            'recentMovements' => FinishedStockMovement::query()->with('productVariant')->latest('occurred_at')->limit(5)->get(),
        ];
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
