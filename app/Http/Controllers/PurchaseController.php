<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\MaterialStockMovement;
use App\Models\MeasurementUnit;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'items'])
            ->when($request->filled('search'), fn ($query) => $query->where('purchase_number', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('purchases.index', ['purchases' => $purchases]);
    }

    public function create(): View
    {
        $data = $this->formData();

        return view('purchases.create', $data + [
            'materialOptions' => $data['materials']->map(fn (Material $material): array => [
                'value' => $material->id,
                'label' => $material->name,
                'sublabel' => $material->sku ? 'SKU '.$material->sku : null,
                'unit_id' => $material->base_unit_id,
            ])->values(),
        ]);
    }

    public function store(Request $request, PurchaseService $service): RedirectResponse
    {
        $purchase = $service->create($this->validatedPurchase($request), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('status', 'Purchase created.');
    }

    public function show(Purchase $purchase): View
    {
        return view('purchases.show', [
            'purchase' => $purchase->load(['supplier', 'items.material.baseUnit', 'items.unit', 'items.batches', 'attachments']),
        ]);
    }

    public function receive(Request $request, Purchase $purchase, PurchaseService $service): RedirectResponse
    {
        $data = $request->validate([
            'received_date' => ['required', 'date'],
            'batches' => ['array'],
            'batches.*.purchase_item_id' => ['required', 'exists:purchase_items,id'],
            'batches.*.lot_number' => ['nullable', 'string', 'max:120'],
            'batches.*.expiry_date' => ['nullable', 'date'],
        ]);

        $service->receive($purchase->load('items'), $data, $request->user());

        return back()->with('status', 'Purchase received into material stock.');
    }

    public function returnBatch(Request $request, MaterialBatch $batch, PurchaseService $service): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->returnBatch($batch->load('purchaseItem.purchase'), $data, $request->user());

        return back()->with('status', 'Purchase return recorded.');
    }

    public function movements(): View
    {
        return view('materials.movements', [
            'movements' => MaterialStockMovement::query()->with(['material', 'batch'])->latest('occurred_at')->paginate(20),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'suppliers' => Supplier::query()->where('status', 'active')->orderBy('name')->get(),
            'materials' => Material::query()->where('status', 'active')->orderBy('name')->get(),
            'units' => MeasurementUnit::query()->orderBy('type')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPurchase(Request $request): array
    {
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn (array $item) => filled($item['material_id'] ?? null))
                ->values()
                ->all(),
        ]);

        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['array'],
            'attachments.*.original_name' => ['nullable', 'string', 'max:255'],
            'attachments.*.path' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['required', 'exists:materials,id'],
            'items.*.unit_id' => ['required', 'exists:measurement_units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
