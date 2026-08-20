<?php

namespace App\Services;

use App\Models\CreditInvoice;
use App\Models\Expense;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\ProductionBatch;
use App\Models\ProductionMaterialUsage;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\SalePayment;
use App\Models\StockAdjustment;
use App\Models\StocktakeItem;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public const REPORTS = [
        'daily_sales' => 'Daily sales',
        'weekly_sales' => 'Weekly sales',
        'monthly_sales' => 'Monthly sales',
        'yearly_sales' => 'Yearly sales',
        'sales_by_product' => 'Sales by product',
        'sales_by_category' => 'Sales by category',
        'sales_by_customer' => 'Sales by customer',
        'sales_by_user' => 'Sales by user',
        'sales_by_sale_type' => 'Sales by sale type',
        'sales_by_payment_method' => 'Sales by payment method',
        'outstanding_credit' => 'Outstanding credit',
        'credit_aging' => 'Credit aging',
        'inventory_balances' => 'Inventory balances',
        'stock_movements' => 'Stock movements',
        'batch_expiry' => 'Batch and expiry',
        'damage_spoilage' => 'Damage and spoilage',
        'stocktake_variance' => 'Stocktake variance',
        'production_output' => 'Production output',
        'material_usage' => 'Material usage',
        'yield_wastage' => 'Yield and wastage',
        'purchases' => 'Purchases',
        'suppliers' => 'Suppliers',
        'expenses' => 'Expenses',
        'gross_margin_estimates' => 'Operational gross-margin estimates',
    ];

    public function report(string $type, Carbon $from, Carbon $to): array
    {
        return match ($type) {
            'daily_sales' => $this->groupedSales($from, $to, fn (Sale $sale) => $sale->sale_date->format('Y-m-d')),
            'weekly_sales' => $this->groupedSales($from, $to, fn (Sale $sale) => $sale->sale_date->format('o-\WW')),
            'monthly_sales' => $this->groupedSales($from, $to, fn (Sale $sale) => $sale->sale_date->format('Y-m')),
            'yearly_sales' => $this->groupedSales($from, $to, fn (Sale $sale) => $sale->sale_date->format('Y')),
            'sales_by_product' => $this->salesByProduct($from, $to),
            'sales_by_category' => $this->salesByCategory($from, $to),
            'sales_by_customer' => $this->salesByRelation($from, $to, 'customer', 'Customer'),
            'sales_by_user' => $this->salesByUser($from, $to),
            'sales_by_sale_type' => $this->salesByRelation($from, $to, 'saleType', 'Sale type'),
            'sales_by_payment_method' => $this->paymentMethodReport($from, $to),
            'outstanding_credit' => $this->creditReport(),
            'credit_aging' => $this->creditAging(),
            'inventory_balances' => $this->inventoryBalances(),
            'stock_movements' => $this->stockMovements($from, $to),
            'batch_expiry' => $this->batchExpiry(),
            'damage_spoilage' => $this->damageSpoilage($from, $to),
            'stocktake_variance' => $this->stocktakeVariance($from, $to),
            'production_output' => $this->productionOutput($from, $to),
            'material_usage' => $this->materialUsage($from, $to),
            'yield_wastage' => $this->yieldWastage($from, $to),
            'purchases' => $this->purchases($from, $to),
            'suppliers' => $this->suppliers(),
            'expenses' => $this->expenses($from, $to),
            'gross_margin_estimates' => $this->grossMargin($from, $to),
            default => throw new \InvalidArgumentException('Unsupported report type.'),
        };
    }

    private function groupedSales(Carbon $from, Carbon $to, callable $label): array
    {
        $rows = $this->sales($from, $to)->groupBy($label)->map(fn (Collection $sales, string $period) => [
            $period,
            $sales->count(),
            number_format((float) $sales->sum('total'), 2, '.', ''),
        ])->values();

        return ['headers' => ['Period', 'Sales count', 'Total GHS'], 'rows' => $rows];
    }

    private function salesByProduct(Carbon $from, Carbon $to): array
    {
        $items = SaleItem::query()->with('productVariant')->whereHas('sale', fn ($query) => $this->dateFilter($query, $from, $to))->get();

        return [
            'headers' => ['Product', 'Quantity', 'Total GHS'],
            'rows' => $items->groupBy('product_variant_id')->map(fn (Collection $rows) => [
                $rows->first()->productVariant?->name ?? 'Unknown',
                number_format((float) $rows->sum('quantity'), 3, '.', ''),
                number_format((float) $rows->sum('line_total'), 2, '.', ''),
            ])->values(),
        ];
    }

    private function salesByCategory(Carbon $from, Carbon $to): array
    {
        $items = SaleItem::query()->with('productVariant.category')->whereHas('sale', fn ($query) => $this->dateFilter($query, $from, $to))->get();

        return [
            'headers' => ['Category', 'Quantity', 'Total GHS'],
            'rows' => $items->groupBy(fn (SaleItem $item) => $item->productVariant?->category?->name ?? 'Uncategorized')->map(fn (Collection $rows, string $category) => [
                $category,
                number_format((float) $rows->sum('quantity'), 3, '.', ''),
                number_format((float) $rows->sum('line_total'), 2, '.', ''),
            ])->values(),
        ];
    }

    private function salesByRelation(Carbon $from, Carbon $to, string $relation, string $label): array
    {
        $sales = $this->sales($from, $to)->load($relation);

        return [
            'headers' => [$label, 'Sales count', 'Total GHS'],
            'rows' => $sales->groupBy(fn (Sale $sale) => $sale->{$relation}?->name ?? 'Unassigned')->map(fn (Collection $rows, string $name) => [
                $name,
                $rows->count(),
                number_format((float) $rows->sum('total'), 2, '.', ''),
            ])->values(),
        ];
    }

    private function salesByUser(Carbon $from, Carbon $to): array
    {
        $sales = $this->sales($from, $to)->load('customer');

        return ['headers' => ['Created by user ID', 'Sales count', 'Total GHS'], 'rows' => $sales->groupBy('created_by')->map(fn (Collection $rows, mixed $userId) => [$userId ?: 'Unknown', $rows->count(), number_format((float) $rows->sum('total'), 2, '.', '')])->values()];
    }

    private function paymentMethodReport(Carbon $from, Carbon $to): array
    {
        $payments = SalePayment::query()->whereBetween('received_at', [$from->startOfDay(), $to->endOfDay()])->get();

        return ['headers' => ['Payment method', 'Payments', 'Amount GHS'], 'rows' => $payments->groupBy('payment_method')->map(fn (Collection $rows, string $method) => [$method, $rows->count(), number_format((float) $rows->sum('amount'), 2, '.', '')])->values()];
    }

    private function creditReport(): array
    {
        $rows = CreditInvoice::query()->with('customer')->where('balance', '>', 0)->get()->map(fn (CreditInvoice $invoice) => [$invoice->invoice_number, $invoice->customer?->name, $invoice->due_date->toDateString(), $invoice->status, $invoice->balance]);

        return ['headers' => ['Invoice', 'Customer', 'Due date', 'Status', 'Balance GHS'], 'rows' => $rows];
    }

    private function creditAging(): array
    {
        $rows = CreditInvoice::query()->where('balance', '>', 0)->get()->groupBy('status')->map(fn (Collection $items, string $status) => [$status, $items->count(), number_format((float) $items->sum('balance'), 2, '.', '')])->values();

        return ['headers' => ['Status', 'Invoices', 'Balance GHS'], 'rows' => $rows];
    }

    private function inventoryBalances(): array
    {
        $finished = FinishedProductBatch::query()->with('productVariant')->get()->groupBy('product_variant_id')->map(fn (Collection $batches) => ['Finished: '.$batches->first()->productVariant?->name, number_format((float) $batches->sum('quantity_available'), 3, '.', ''), number_format((float) $batches->sum(fn ($batch) => (float) $batch->quantity_available * (float) $batch->unit_cost), 2, '.', '')]);
        $raw = Material::query()->with('batches')->get()->map(fn (Material $material) => ['Raw: '.$material->name, number_format((float) $material->batches->sum('quantity_available'), 3, '.', ''), number_format((float) $material->batches->sum(fn ($batch) => (float) $batch->quantity_available * (float) $batch->unit_cost), 2, '.', '')]);

        return ['headers' => ['Item', 'Available quantity', 'Estimated value GHS'], 'rows' => $raw->concat($finished)->values()];
    }

    private function stockMovements(Carbon $from, Carbon $to): array
    {
        $raw = MaterialStockMovement::query()->with('material')->whereBetween('occurred_at', [$from->startOfDay(), $to->endOfDay()])->get()->map(fn ($movement) => ['Raw', $movement->material?->name, $movement->movement_type, $movement->quantity, $movement->occurred_at->toDateString()]);
        $finished = FinishedStockMovement::query()->with('productVariant')->whereBetween('occurred_at', [$from->startOfDay(), $to->endOfDay()])->get()->map(fn ($movement) => ['Finished', $movement->productVariant?->name, $movement->movement_type, $movement->quantity, $movement->occurred_at->toDateString()]);

        return ['headers' => ['Stock type', 'Item', 'Movement', 'Quantity', 'Date'], 'rows' => $raw->concat($finished)->values()];
    }

    private function batchExpiry(): array
    {
        return ['headers' => ['Batch', 'Product', 'Available', 'Expiry date', 'Status'], 'rows' => FinishedProductBatch::query()->with('productVariant')->orderBy('expiry_date')->get()->map(fn ($batch) => [$batch->batch_number, $batch->productVariant?->name, $batch->quantity_available, $batch->expiry_date?->toDateString(), $batch->status])];
    }

    private function damageSpoilage(Carbon $from, Carbon $to): array
    {
        return ['headers' => ['Type', 'Quantity', 'Reason', 'Date'], 'rows' => StockAdjustment::query()->whereIn('adjustment_type', ['damage', 'spoilage', 'expired'])->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->get()->map(fn ($adjustment) => [$adjustment->adjustment_type, $adjustment->quantity, $adjustment->reason, $adjustment->created_at->toDateString()])];
    }

    private function stocktakeVariance(Carbon $from, Carbon $to): array
    {
        return ['headers' => ['Stocktake date', 'System', 'Counted', 'Variance', 'Notes'], 'rows' => StocktakeItem::query()->with('stocktake')->whereHas('stocktake', fn ($query) => $query->whereBetween('stocktake_date', [$from->toDateString(), $to->toDateString()]))->get()->map(fn ($item) => [$item->stocktake?->stocktake_date?->toDateString(), $item->system_quantity, $item->counted_quantity, $item->variance_quantity, $item->notes])];
    }

    private function productionOutput(Carbon $from, Carbon $to): array
    {
        return ['headers' => ['Batch', 'Product', 'Output', 'Rejected', 'Status', 'Date'], 'rows' => ProductionBatch::query()->with('productVariant')->whereBetween('production_date', [$from->toDateString(), $to->toDateString()])->get()->map(fn ($batch) => [$batch->batch_number, $batch->productVariant?->name, $batch->actual_output_quantity, $batch->rejected_quantity, $batch->status, $batch->production_date?->toDateString()])];
    }

    private function materialUsage(Carbon $from, Carbon $to): array
    {
        return ['headers' => ['Material', 'Usage type', 'Actual quantity', 'Line cost GHS'], 'rows' => ProductionMaterialUsage::query()->with('material', 'productionBatch')->whereHas('productionBatch', fn ($query) => $query->whereBetween('production_date', [$from->toDateString(), $to->toDateString()]))->get()->map(fn ($usage) => [$usage->material?->name, $usage->usage_type, $usage->actual_quantity, $usage->line_cost])];
    }

    private function yieldWastage(Carbon $from, Carbon $to): array
    {
        return ['headers' => ['Batch', 'Yield %', 'Wastage', 'Rejected'], 'rows' => ProductionBatch::query()->whereBetween('production_date', [$from->toDateString(), $to->toDateString()])->get()->map(fn ($batch) => [$batch->batch_number, $batch->yield_percent, $batch->wastage_quantity, $batch->rejected_quantity])];
    }

    private function purchases(Carbon $from, Carbon $to): array
    {
        return ['headers' => ['Purchase', 'Supplier', 'Date', 'Status', 'Total GHS'], 'rows' => Purchase::query()->with('supplier')->whereBetween('purchase_date', [$from->toDateString(), $to->toDateString()])->get()->map(fn ($purchase) => [$purchase->purchase_number, $purchase->supplier?->name, $purchase->purchase_date->toDateString(), $purchase->status, $purchase->subtotal])];
    }

    private function suppliers(): array
    {
        return ['headers' => ['Supplier', 'Phone', 'Email', 'Status'], 'rows' => Supplier::query()->orderBy('name')->get()->map(fn ($supplier) => [$supplier->name, $supplier->phone, $supplier->email, $supplier->status])];
    }

    private function expenses(Carbon $from, Carbon $to): array
    {
        return ['headers' => ['Expense', 'Category', 'Date', 'Status', 'Amount GHS'], 'rows' => Expense::query()->with('category')->whereBetween('incurred_on', [$from->toDateString(), $to->toDateString()])->get()->map(fn ($expense) => [$expense->expense_number, $expense->category?->name, $expense->incurred_on->toDateString(), $expense->status, $expense->amount])];
    }

    private function grossMargin(Carbon $from, Carbon $to): array
    {
        $items = SaleItem::query()->with('productVariant', 'batches')->whereHas('sale', fn ($query) => $this->dateFilter($query, $from, $to))->get();

        return ['headers' => ['Product', 'Sales GHS', 'Estimated cost GHS', 'Estimated gross margin GHS'], 'rows' => $items->groupBy('product_variant_id')->map(function (Collection $rows): array {
            $sales = (float) $rows->sum('line_total');
            $cost = (float) SaleItemBatch::query()->whereIn('sale_item_id', $rows->pluck('id'))->get()->sum(fn ($batch) => (float) $batch->quantity * (float) $batch->unit_cost);

            return [$rows->first()->productVariant?->name, number_format($sales, 2, '.', ''), number_format($cost, 2, '.', ''), number_format($sales - $cost, 2, '.', '')];
        })->values()];
    }

    private function sales(Carbon $from, Carbon $to): Collection
    {
        return Sale::query()->where('status', 'completed')->whereBetween('sale_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->get();
    }

    private function dateFilter($query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('sale_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }
}
