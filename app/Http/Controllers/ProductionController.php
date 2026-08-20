<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use App\Models\ProductionPlan;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Services\RecipeProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function plans(): View
    {
        return view('production.plans', [
            'plans' => ProductionPlan::query()->with(['productVariant.type', 'productVariant.size', 'recipe'])->latest()->paginate(15),
        ]);
    }

    public function createPlan(): View
    {
        return view('production.plan-create', [
            'variants' => ProductVariant::query()->with(['type', 'size'])->orderBy('name')->get(),
            'recipes' => Recipe::query()->where('status', 'approved')->with('productVariant')->orderByDesc('version')->get(),
        ]);
    }

    public function storePlan(Request $request, RecipeProductionService $service): RedirectResponse
    {
        $plan = $service->createPlan($request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'recipe_id' => ['nullable', 'exists:recipes,id'],
            'planned_date' => ['required', 'date'],
            'planned_quantity' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]), $request->user());

        return redirect()->route('production.plans')->with('status', 'Production plan created.');
    }

    public function batches(): View
    {
        return view('production.batches', [
            'batches' => ProductionBatch::query()->with(['productVariant.type', 'productVariant.size', 'recipe'])->latest()->paginate(15),
        ]);
    }

    public function createBatch(): View
    {
        return view('production.batch-create', [
            'recipes' => Recipe::query()->where('status', 'approved')->with(['productVariant.type', 'productVariant.size'])->orderByDesc('version')->get(),
            'plans' => ProductionPlan::query()->where('status', 'planned')->with('productVariant')->latest()->get(),
        ]);
    }

    public function storeBatch(Request $request, RecipeProductionService $service): RedirectResponse
    {
        $batch = $service->createBatch($request->validate([
            'recipe_id' => ['required', 'exists:recipes,id'],
            'production_plan_id' => ['nullable', 'exists:production_plans,id'],
            'production_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:production_date'],
            'planned_quantity' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]), $request->user());

        return redirect()->route('production.batches.show', $batch)->with('status', 'Production batch created.');
    }

    public function showBatch(ProductionBatch $batch): View
    {
        return view('production.batch-show', [
            'batch' => $batch->load(['productVariant.type', 'productVariant.size', 'recipe.materials.material', 'recipe.materials.unit', 'usages.material', 'usages.materialBatch', 'output']),
        ]);
    }

    public function completeBatch(Request $request, ProductionBatch $batch, RecipeProductionService $service): RedirectResponse
    {
        $service->completeBatch($batch->load('recipe'), $request->validate([
            'actual_output_quantity' => ['required', 'numeric', 'min:0'],
            'rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'wastage_quantity' => ['nullable', 'numeric', 'min:0'],
            'usages' => ['array'],
            'usages.*.recipe_material_id' => ['required', 'exists:recipe_materials,id'],
            'usages.*.actual_quantity' => ['nullable', 'numeric', 'min:0.001'],
        ]), $request->user());

        return back()->with('status', 'Production batch completed.');
    }

    public function reverseBatch(Request $request, ProductionBatch $batch, RecipeProductionService $service): RedirectResponse
    {
        $service->reverseBatch($batch, $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]), $request->user());

        return back()->with('status', 'Production batch reversed.');
    }
}
