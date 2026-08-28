<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Services\ProductCatalogueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductCatalogueController extends Controller
{
    public function index(Request $request): View
    {
        $variants = ProductVariant::query()
            ->with(['category', 'type', 'size.unit'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('product_category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('catalogue.index', [
            'variants' => $variants,
            'categories' => ProductCategory::query()->withCount(['types', 'sizes'])->orderBy('sort_order')->get(),
            'statusCounts' => ProductVariant::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
        ]);
    }

    public function edit(ProductVariant $variant): View
    {
        $this->authorize('update', $variant);

        return view('catalogue.edit', [
            'variant' => $variant->load(['category', 'type', 'size.unit']),
        ]);
    }

    public function update(Request $request, ProductVariant $variant, ProductCatalogueService $service): RedirectResponse
    {
        $this->authorize('update', $variant);

        $data = $this->validatedVariant($request, $variant);

        if ($request->hasFile('image')) {
            $oldPath = $variant->image_path;
            $data['image_path'] = $request->file('image')->store('product-images', 'public');

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $service->updateVariant($variant, $data, $request->user());

        return redirect()->route('catalogue.index')->with('status', 'Product variant updated.');
    }

    public function status(Request $request, ProductVariant $variant, ProductCatalogueService $service): RedirectResponse
    {
        $this->authorize('changeStatus', $variant);

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ]);

        $service->setVariantStatus($variant, $data['status'], $request->user());

        return back()->with('status', 'Product variant status updated.');
    }

    public function export(Request $request): Response
    {
        $filename = 'ivys-food-product-catalogue-'.now()->format('Ymd-His').'.csv';
        $variants = ProductVariant::query()->with(['category', 'type', 'size'])->orderBy('name')->get();

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Category', 'Type', 'Size', 'Variant', 'SKU', 'Retail Price', 'Wholesale Price', 'Distributor Price', 'Status', 'Reorder Level', 'Shelf Life Days']);

        foreach ($variants as $variant) {
            fputcsv($csv, [
                $variant->category->name,
                $variant->type->name,
                $variant->size->label,
                $variant->name,
                $variant->sku,
                $variant->retail_price,
                $variant->wholesale_price,
                $variant->distributor_price,
                $variant->status,
                $variant->reorder_level,
                $variant->shelf_life_days,
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedVariant(Request $request, ProductVariant $variant): array
    {
        return $request->validate([
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('product_variants', 'sku')->ignore($variant)],
            'retail_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'distributor_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'reorder_level' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);
    }
}
