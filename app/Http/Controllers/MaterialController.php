<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(): View
    {
        return view('materials.index', [
            'materials' => Material::query()->with(['category', 'baseUnit', 'batches'])->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('materials.create', $this->formData());
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $material = Material::query()->create($data);
        $audit->record('material.created', $material, [], $material->toArray(), actor: $request->user());

        return redirect()->route('materials.index')->with('status', 'Material created.');
    }

    public function edit(Material $material): View
    {
        return view('materials.edit', $this->formData($material));
    }

    public function update(Request $request, Material $material, AuditService $audit): RedirectResponse
    {
        $data = $this->validated($request, $material);
        $old = $material->toArray();
        $material->update($data);
        $audit->record('material.updated', $material, $old, $material->fresh()->toArray(), actor: $request->user());

        return redirect()->route('materials.index')->with('status', 'Material updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Material $material = null): array
    {
        return [
            'material' => $material,
            'categories' => MaterialCategory::query()->orderBy('type')->orderBy('name')->get(),
            'units' => MeasurementUnit::query()->orderBy('type')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Material $material = null): array
    {
        return $request->validate([
            'material_category_id' => ['required', 'exists:material_categories,id'],
            'base_unit_id' => ['required', 'exists:measurement_units,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('materials', 'name')->ignore($material)],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('materials', 'sku')->ignore($material)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'track_lot' => ['nullable', 'boolean'],
            'track_expiry' => ['nullable', 'boolean'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]) + [
            'track_lot' => $request->boolean('track_lot'),
            'track_expiry' => $request->boolean('track_expiry'),
        ];
    }
}
