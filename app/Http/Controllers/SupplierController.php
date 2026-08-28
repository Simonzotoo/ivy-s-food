<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('suppliers.index', [
            'suppliers' => Supplier::query()->withCount('purchases')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create', ['supplier' => null]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $supplier = Supplier::query()->create($this->validated($request));
        $audit->record('supplier.created', $supplier, [], $supplier->toArray(), actor: $request->user());

        return redirect()->route('suppliers.index')->with('status', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier, AuditService $audit): RedirectResponse
    {
        $old = $supplier->toArray();
        $supplier->update($this->validated($request, $supplier));
        $audit->record('supplier.updated', $supplier, $old, $supplier->fresh()->toArray(), actor: $request->user());

        return redirect()->route('suppliers.index')->with('status', 'Supplier updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplier)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
