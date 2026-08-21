<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MeasurementUnit;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Services\RecipeProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(): View
    {
        return view('recipes.index', [
            'recipes' => Recipe::query()->with(['productVariant.category', 'productVariant.type', 'productVariant.size', 'materials'])->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        $data = $this->formData();

        return view('recipes.create', $data + [
            'materialOptions' => $data['materials']->map(fn (Material $material): array => [
                'value' => $material->id,
                'label' => $material->name,
                'sublabel' => $material->sku ? 'SKU '.$material->sku : null,
                'unit_id' => $material->base_unit_id,
            ])->values(),
        ]);
    }

    public function store(Request $request, RecipeProductionService $service): RedirectResponse
    {
        $recipe = $service->createRecipe($this->validatedRecipe($request), $request->user());

        return redirect()->route('recipes.show', $recipe)->with('status', 'Recipe created as draft.');
    }

    public function show(Recipe $recipe): View
    {
        return view('recipes.show', [
            'recipe' => $recipe->load(['productVariant.category', 'productVariant.type', 'productVariant.size', 'materials.material', 'materials.unit', 'outputUnit']),
        ]);
    }

    public function approve(Request $request, Recipe $recipe, RecipeProductionService $service): RedirectResponse
    {
        $service->approveRecipe($recipe, $request->user());

        return back()->with('status', 'Recipe approved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'variants' => ProductVariant::query()->with(['category', 'type', 'size'])->orderBy('name')->get(),
            'materials' => Material::query()->where('status', 'active')->orderBy('name')->get(),
            'units' => MeasurementUnit::query()->orderBy('type')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedRecipe(Request $request): array
    {
        $request->merge([
            'materials' => collect($request->input('materials', []))
                ->filter(fn (array $item) => filled($item['material_id'] ?? null))
                ->values()
                ->all(),
        ]);

        return $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'expected_output_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'output_unit_id' => ['nullable', 'exists:measurement_units,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.material_id' => ['required', 'exists:materials,id'],
            'materials.*.unit_id' => ['required', 'exists:measurement_units,id'],
            'materials.*.usage_type' => ['required', 'in:ingredient,packaging'],
            'materials.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'materials.*.wastage_allowance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}
