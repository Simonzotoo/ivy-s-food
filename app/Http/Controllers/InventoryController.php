<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\MaterialStockMovement;
use App\Models\ProductionOutput;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\Stocktake;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $settings = CompanySetting::query()->first();
        $expiryDays = $settings?->default_expiry_alert_days ?? 14;

        $materials = Material::query()->with(['category', 'baseUnit', 'batches'])->orderBy('name')->get();
        $variants = ProductVariant::query()->with('finishedBatches')->orderBy('name')->get();

        $needsAttention = FinishedProductBatch::query()
            ->with('productVariant')
            ->where(fn ($q) => $q->where('quantity_damaged', '>', 0)->orWhere('quantity_quarantined', '>', 0))
            ->get();

        return view('inventory.index', [
            'materials' => $materials,
            'variants' => $variants,
            'lowRawMaterials' => $materials->filter(fn (Material $m) => $m->reorder_level !== null && (float) $m->availableQuantity() <= (float) $m->reorder_level),
            'lowFinishedProducts' => $variants->filter(fn (ProductVariant $v) => $v->reorder_level !== null && (float) $v->finishedBatches->sum('quantity_available') <= (float) $v->reorder_level),
            'pendingOutputs' => ProductionOutput::query()->where('status', 'pending_inventory')->with('productVariant', 'productionBatch')->latest()->get(),
            'nearExpiryFinished' => FinishedProductBatch::query()->with('productVariant')->whereDate('expiry_date', '>=', now()->toDateString())->whereDate('expiry_date', '<=', now()->addDays($expiryDays))->where('quantity_available', '>', 0)->get(),
            'expiredFinished' => FinishedProductBatch::query()->with('productVariant')->whereDate('expiry_date', '<', now()->toDateString())->where('quantity_available', '>', 0)->get(),
            'needsAttention' => $needsAttention,
        ]);
    }

    public function finishedBatches(): View
    {
        return view('inventory.finished-batches', [
            'batches' => FinishedProductBatch::query()->with('productVariant')->latest()->paginate(20),
        ]);
    }

    public function movements(): View
    {
        return view('inventory.movements', [
            'rawMovements' => MaterialStockMovement::query()->with(['material', 'batch'])->latest('occurred_at')->limit(20)->get(),
            'finishedMovements' => FinishedStockMovement::query()->with(['productVariant', 'batch'])->latest('occurred_at')->limit(20)->get(),
        ]);
    }

    public function adjustments(): View
    {
        $rawBatches = MaterialBatch::query()->with('material')->where('quantity_available', '>', 0)->latest()->get();
        $finishedBatches = FinishedProductBatch::query()->with('productVariant')->where('quantity_available', '>', 0)->latest()->get();

        return view('inventory.adjustments', [
            'adjustments' => StockAdjustment::query()->with(['materialBatch.material', 'finishedProductBatch.productVariant'])->latest()->paginate(20),
            'rawBatches' => $rawBatches,
            'finishedBatches' => $finishedBatches,
            'batchOptions' => $rawBatches->map(fn (MaterialBatch $batch): array => [
                'value' => $batch->id,
                'stock_type' => 'raw',
                'label' => 'Raw: '.$batch->material->name.' #'.$batch->id,
                'sublabel' => 'Available '.$batch->quantity_available,
            ])->values()->concat($finishedBatches->map(fn (FinishedProductBatch $batch): array => [
                'value' => $batch->id,
                'stock_type' => 'finished',
                'label' => 'Finished: '.$batch->productVariant->name.' '.$batch->batch_number,
                'sublabel' => 'Available '.$batch->quantity_available,
            ])->values()),
        ]);
    }

    public function storeAdjustment(Request $request, InventoryService $service): RedirectResponse
    {
        $data = $request->validate([
            'stock_type' => ['required', 'in:raw,finished'],
            'batch_id' => ['required', 'integer'],
            'adjustment_type' => ['required', 'in:increase,decrease,damage,spoilage,sample,internal_use,expired,quarantine,release_quarantine'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($data['stock_type'] === 'raw') {
            $service->adjustRaw(MaterialBatch::query()->findOrFail($data['batch_id']), $data['adjustment_type'], $data['quantity'], $data['reason'], $request->user());
        } else {
            $service->adjustFinished(FinishedProductBatch::query()->findOrFail($data['batch_id']), $data['adjustment_type'], $data['quantity'], $data['reason'], $request->user());
        }

        return back()->with('status', 'Stock adjustment recorded.');
    }

    public function postProductionOutput(Request $request, ProductionOutput $output, InventoryService $service): RedirectResponse
    {
        $service->postProductionOutput($output, $request->user());

        return back()->with('status', 'Production output posted to finished inventory.');
    }

    public function stocktakes(): View
    {
        return view('inventory.stocktakes', [
            'stocktakes' => Stocktake::query()->withCount('items')->latest()->paginate(20),
        ]);
    }

    public function createStocktake(): View
    {
        $rawBatches = MaterialBatch::query()->with('material')->where('quantity_available', '>', 0)->latest()->limit(30)->get();
        $finishedBatches = FinishedProductBatch::query()->with('productVariant')->where('quantity_available', '>', 0)->latest()->limit(30)->get();

        return view('inventory.stocktake-create', [
            'rawBatches' => $rawBatches,
            'finishedBatches' => $finishedBatches,
            'rawBatchOptions' => $rawBatches->map(fn (MaterialBatch $batch): array => [
                'value' => $batch->id,
                'label' => $batch->material->name.' #'.$batch->id,
                'sublabel' => 'System quantity: '.$batch->quantity_available,
            ])->values(),
            'finishedBatchOptions' => $finishedBatches->map(fn (FinishedProductBatch $batch): array => [
                'value' => $batch->id,
                'label' => $batch->productVariant->name.' '.$batch->batch_number,
                'sublabel' => 'System quantity: '.$batch->quantity_available,
            ])->values(),
        ]);
    }

    public function storeStocktake(Request $request, InventoryService $service): RedirectResponse
    {
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn (array $item) => filled($item['batch_id'] ?? null) && filled($item['counted_quantity'] ?? null))
                ->values()
                ->all(),
        ]);

        $stocktake = $service->createStocktake($request->validate([
            'stock_type' => ['required', 'in:raw,finished'],
            'stocktake_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.batch_id' => ['required', 'integer'],
            'items.*.counted_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]), $request->user());

        return redirect()->route('inventory.stocktakes')->with('status', 'Stocktake recorded for review.');
    }

    public function approveStocktake(Request $request, Stocktake $stocktake, InventoryService $service): RedirectResponse
    {
        $service->approveStocktake($stocktake->load('items.materialBatch', 'items.finishedProductBatch'), $request->user());

        return back()->with('status', 'Stocktake variances approved.');
    }
}
