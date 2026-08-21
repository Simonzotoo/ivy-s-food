<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleType;
use App\Services\SalesService;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(Request $request): View
    {
        $sales = Sale::query()
            ->with(['customer', 'saleType', 'items'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where(fn ($inner) => $inner->where('sale_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")));
            })
            ->when($request->filled('sale_type'), fn ($query) => $query->where('sale_type_id', $request->integer('sale_type')))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->string('payment_status')))
            ->latest('sale_date')
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'saleTypes' => SaleType::query()->orderBy('name')->get(),
        ]);
    }

    public function pos(): View
    {
        $variants = ProductVariant::query()->where('status', 'active')->orderBy('name')->get();
        $customers = Customer::query()->where('status', 'active')->orderBy('name')->get();

        return view('sales.pos', [
            'variantOptions' => $variants->map(fn (ProductVariant $variant): array => [
                'value' => $variant->id,
                'label' => $variant->name,
                'sublabel' => $variant->barcode ? 'Barcode '.$variant->barcode : ($variant->sku ? 'SKU '.$variant->sku : null),
                'retail' => $variant->retail_price,
                'wholesale' => $variant->wholesale_price,
                'distributor' => $variant->distributor_price,
            ])->values(),
            'customerOptions' => $customers->map(fn (Customer $customer): array => [
                'value' => $customer->id,
                'label' => $customer->name,
                'sublabel' => $customer->normalized_phone,
            ])->values(),
            'saleTypes' => SaleType::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(Request $request, SalesService $service): RedirectResponse
    {
        $sale = $service->createSale($this->validatedSale($request), $request->user());

        return redirect()->route('sales.show', $sale)->with('status', 'Sale completed.');
    }

    public function show(Sale $sale): View
    {
        return view('sales.show', [
            'sale' => $sale->load(['customer', 'saleType', 'items.productVariant', 'payments', 'creditInvoice', 'deliveryNote']),
        ]);
    }

    public function receipt(Sale $sale): View
    {
        return view('sales.receipt', ['sale' => $sale->load(['customer', 'saleType', 'items.productVariant', 'payments', 'creditInvoice', 'deliveryNote'])]);
    }

    public function pdf(Sale $sale, string $document): Response
    {
        abort_unless(in_array($document, ['receipt', 'invoice', 'delivery-note'], true), 404);

        $sale->load(['customer', 'saleType', 'items.productVariant', 'payments', 'creditInvoice', 'deliveryNote']);
        $html = view('sales.pdf', ['sale' => $sale, 'document' => $document])->render();
        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$sale->sale_number.'-'.$document.'.pdf"',
        ]);
    }

    public function reverse(Request $request, Sale $sale, SalesService $service): RedirectResponse
    {
        $service->reverseSale($sale, $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]), $request->user());

        return back()->with('status', 'Sale reversed.');
    }

    public function returnItems(Request $request, Sale $sale, SalesService $service): RedirectResponse
    {
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn (array $item) => filled($item['sale_item_id'] ?? null) && filled($item['quantity'] ?? null))
                ->values()
                ->all(),
        ]);

        $service->returnItems($sale, $request->validate([
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]), $request->user());

        return back()->with('status', 'Sale return recorded.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSale(Request $request): array
    {
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn (array $item) => filled($item['product_variant_id'] ?? null))
                ->values()
                ->all(),
            'payments' => collect($request->input('payments', []))
                ->filter(fn (array $payment) => filled($payment['payment_method'] ?? null))
                ->values()
                ->all(),
        ]);

        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'sale_type_id' => ['required', 'exists:sale_types,id'],
            'sale_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', 'in:Cash,MoMo,Credit'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.momo_network' => ['nullable', 'string', 'max:50'],
            'payments.*.transaction_reference' => ['nullable', 'string', 'max:120'],
        ]);

        if (! $request->user()->can('sales.discount')) {
            foreach ($data['items'] as &$item) {
                unset($item['discount_amount']);
            }
        }

        return $data;
    }
}
