<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\MaterialCategory;
use App\Models\MaterialStockMovement;
use App\Models\MeasurementUnit;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Module3MaterialsPurchasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingsSeeder::class);
        $this->seed(ProductCatalogueSeeder::class);
        $this->seed(MaterialFoundationSeeder::class);
    }

    public function test_material_foundation_seeds_only_generic_categories_and_units(): void
    {
        $this->assertDatabaseHas('material_categories', ['name' => 'Raw Materials', 'type' => 'raw_material']);
        $this->assertDatabaseHas('material_categories', ['name' => 'Packaging Materials', 'type' => 'packaging_material']);
        $this->assertDatabaseHas('measurement_units', ['symbol' => 'pcs']);
        $this->assertSame(0, Material::query()->count());
        $this->assertSame(0, Supplier::query()->count());
    }

    public function test_material_and_supplier_permissions_are_enforced(): void
    {
        $this->get('/materials')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $purchasing = $this->userWithRole('Purchasing Officer');

        $this->actingAs($cashier)->get('/materials')->assertForbidden();
        $this->actingAs($purchasing)->get('/materials')->assertOk();
        $this->actingAs($purchasing)->get('/suppliers')->assertOk();
    }

    public function test_authorized_user_can_create_material_and_supplier_with_audit(): void
    {
        $purchasing = $this->userWithRole('Purchasing Officer');
        $category = MaterialCategory::query()->where('type', 'raw_material')->firstOrFail();
        $unit = MeasurementUnit::query()->where('symbol', 'kg')->firstOrFail();

        $this->actingAs($purchasing)->post('/materials', [
            'material_category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'name' => 'Configurable Sugar',
            'sku' => 'RAW-SUGAR',
            'status' => 'active',
            'track_lot' => '1',
            'track_expiry' => '1',
            'reorder_level' => '10',
        ])->assertRedirect(route('materials.index'));

        $this->actingAs($purchasing)->post('/suppliers', [
            'name' => 'Configurable Supplier',
            'phone' => '0240000000',
            'status' => 'active',
        ])->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('materials', ['name' => 'Configurable Sugar']);
        $this->assertDatabaseHas('suppliers', ['name' => 'Configurable Supplier']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'material.created']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'supplier.created']);
    }

    public function test_purchase_creation_receiving_and_return_create_stock_movements(): void
    {
        $purchasing = $this->userWithRole('Purchasing Officer');
        [$material, $supplier, $unit] = $this->materialSupplierAndUnit();

        $this->actingAs($purchasing)->post('/purchases', [
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-08-20',
            'items' => [
                ['material_id' => $material->id, 'unit_id' => $unit->id, 'quantity' => '25', 'unit_cost' => '12.5000'],
            ],
            'attachments' => [
                ['original_name' => 'Supplier invoice reference', 'path' => 'pending/supplier-invoice.pdf'],
            ],
        ])->assertRedirect();

        $purchase = Purchase::query()->with('items')->firstOrFail();
        $this->assertSame('312.50', (string) $purchase->subtotal);
        $this->assertDatabaseHas('purchase_attachments', ['purchase_id' => $purchase->id, 'original_name' => 'Supplier invoice reference']);

        $this->actingAs($purchasing)->post(route('purchases.receive', $purchase), [
            'received_date' => '2026-08-20',
            'batches' => [
                ['purchase_item_id' => $purchase->items->first()->id, 'lot_number' => 'LOT-001', 'expiry_date' => '2027-08-20'],
            ],
        ])->assertRedirect();

        $batch = MaterialBatch::query()->firstOrFail();
        $this->assertSame('25.000', (string) $batch->quantity_available);
        $this->assertDatabaseHas('material_stock_movements', ['movement_type' => 'purchase_receive', 'material_batch_id' => $batch->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'purchase.received']);

        $this->actingAs($purchasing)->post(route('material-batches.return', $batch), [
            'quantity' => '5',
            'return_date' => '2026-08-21',
            'reason' => 'Damaged packaging on delivery',
        ])->assertRedirect();

        $this->assertSame('20.000', (string) $batch->fresh()->quantity_available);
        $this->assertDatabaseHas('material_stock_movements', ['movement_type' => 'purchase_return', 'quantity' => '-5.000']);
        $this->assertSame(2, MaterialStockMovement::query()->count());
    }

    public function test_purchase_return_cannot_exceed_available_quantity(): void
    {
        $purchasing = $this->userWithRole('Purchasing Officer');
        [$material, $supplier, $unit] = $this->materialSupplierAndUnit();
        $purchase = Purchase::query()->create([
            'purchase_number' => 'PUR-TEST-RETURN',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-08-20',
            'status' => 'received',
        ]);
        $item = $purchase->items()->create([
            'material_id' => $material->id,
            'unit_id' => $unit->id,
            'quantity' => '3',
            'unit_cost' => '1.0000',
            'line_total' => '3.00',
        ]);
        $batch = MaterialBatch::query()->create([
            'material_id' => $material->id,
            'supplier_id' => $supplier->id,
            'purchase_item_id' => $item->id,
            'received_date' => '2026-08-20',
            'quantity_received' => '3',
            'quantity_available' => '3',
            'status' => 'available',
        ]);

        $this->actingAs($purchasing)->post(route('material-batches.return', $batch), [
            'quantity' => '4',
            'return_date' => '2026-08-21',
            'reason' => 'Too much',
        ])->assertSessionHasErrors('quantity');
    }

    public function test_module_three_seeders_are_idempotent(): void
    {
        $counts = [
            MaterialCategory::query()->count(),
            MeasurementUnit::query()->count(),
        ];

        $this->seed(MaterialFoundationSeeder::class);
        $this->seed(MaterialFoundationSeeder::class);

        $this->assertSame($counts, [
            MaterialCategory::query()->count(),
            MeasurementUnit::query()->count(),
        ]);
    }

    /**
     * @return array{0: Material, 1: Supplier, 2: MeasurementUnit}
     */
    private function materialSupplierAndUnit(): array
    {
        $material = Material::query()->first() ?? Material::query()->create([
            'material_category_id' => MaterialCategory::query()->where('type', 'raw_material')->firstOrFail()->id,
            'base_unit_id' => MeasurementUnit::query()->where('symbol', 'kg')->firstOrFail()->id,
            'name' => 'Configurable Sugar',
            'status' => 'active',
        ]);

        $supplier = Supplier::query()->first() ?? Supplier::query()->create([
            'name' => 'Configurable Supplier',
            'status' => 'active',
        ]);

        return [$material, $supplier, MeasurementUnit::query()->where('symbol', 'kg')->firstOrFail()];
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password!234'),
            'status' => 'active',
            'password_change_required' => false,
        ]);
        $user->roles()->attach(Role::query()->where('name', $roleName)->firstOrFail());

        return $user->fresh('roles.permissions');
    }
}
