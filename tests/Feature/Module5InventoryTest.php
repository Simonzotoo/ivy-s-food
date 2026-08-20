<?php

namespace Tests\Feature;

use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\MaterialCategory;
use App\Models\MaterialStockMovement;
use App\Models\MeasurementUnit;
use App\Models\ProductionBatch;
use App\Models\ProductionOutput;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\Role;
use App\Models\Stocktake;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Module5InventoryTest extends TestCase
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

    public function test_inventory_permissions_are_enforced(): void
    {
        $this->get('/inventory')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $inventory = $this->userWithRole('Inventory Officer');

        $this->actingAs($cashier)->get('/inventory')->assertForbidden();
        $this->actingAs($inventory)->get('/inventory')->assertOk();
        $this->actingAs($inventory)->get('/inventory/adjustments')->assertOk();
    }

    public function test_production_output_can_be_posted_to_finished_inventory_once(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $output = $this->productionOutput('PROD-INV-001', '2026-09-20');

        $this->actingAs($inventory)->post(route('inventory.production-outputs.post', $output))->assertRedirect();

        $batch = FinishedProductBatch::query()->firstOrFail();
        $this->assertSame('12.000', (string) $batch->quantity_available);
        $this->assertSame('posted_to_inventory', $output->fresh()->status);
        $this->assertDatabaseHas('finished_stock_movements', ['movement_type' => 'production_output', 'quantity' => '12.000']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'inventory.production_output_posted']);

        $this->actingAs($inventory)->post(route('inventory.production-outputs.post', $output->fresh()))->assertSessionHasErrors('output');
    }

    public function test_finished_stock_reservation_uses_earliest_expiry_first(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $variant = ProductVariant::query()->firstOrFail();
        $later = $this->finishedBatch($variant, 'FIN-LATE', '2026-10-31', '10');
        $earlier = $this->finishedBatch($variant, 'FIN-EARLY', '2026-09-30', '10');

        app(InventoryService::class)->reserveFinishedStock($variant->id, '12', $inventory);

        $this->assertSame('0.000', (string) $earlier->fresh()->quantity_available);
        $this->assertSame('8.000', (string) $later->fresh()->quantity_available);
        $this->assertSame('10.000', (string) $earlier->fresh()->quantity_reserved);
        $this->assertSame('2.000', (string) $later->fresh()->quantity_reserved);
        $this->assertSame(2, FinishedStockMovement::query()->where('movement_type', 'finished_reserved')->count());
    }

    public function test_finished_adjustment_cannot_create_negative_stock(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $batch = $this->finishedBatch(ProductVariant::query()->firstOrFail(), 'FIN-NEG', '2026-09-30', '3');

        $this->actingAs($inventory)->post('/inventory/adjustments', [
            'stock_type' => 'finished',
            'batch_id' => $batch->id,
            'adjustment_type' => 'damage',
            'quantity' => '4',
            'reason' => 'Too much damage',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame('3.000', (string) $batch->fresh()->quantity_available);
    }

    public function test_raw_adjustment_records_stock_movement_and_audit(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $batch = $this->materialBatch('10');

        $this->actingAs($inventory)->post('/inventory/adjustments', [
            'stock_type' => 'raw',
            'batch_id' => $batch->id,
            'adjustment_type' => 'decrease',
            'quantity' => '2',
            'reason' => 'Internal sample',
        ])->assertRedirect();

        $this->assertSame('8.000', (string) $batch->fresh()->quantity_available);
        $this->assertDatabaseHas('material_stock_movements', ['movement_type' => 'stock_adjustment', 'quantity' => '-2.000']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'inventory.raw_adjusted']);
    }

    public function test_stocktake_approval_applies_variance_through_adjustment_ledger(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $manager = $this->userWithRole('Manager');
        $batch = $this->materialBatch('10');

        $this->actingAs($inventory)->post('/inventory/stocktakes', [
            'stock_type' => 'raw',
            'stocktake_date' => '2026-08-20',
            'items' => [
                ['batch_id' => $batch->id, 'counted_quantity' => '8'],
            ],
        ])->assertRedirect(route('inventory.stocktakes'));

        $stocktake = Stocktake::query()->firstOrFail();
        $this->actingAs($manager)->post(route('inventory.stocktakes.approve', $stocktake))->assertRedirect();

        $this->assertSame('approved', $stocktake->fresh()->status);
        $this->assertSame('8.000', (string) $batch->fresh()->quantity_available);
        $this->assertDatabaseHas('audit_logs', ['event' => 'inventory.stocktake_approved']);
        $this->assertSame(1, MaterialStockMovement::query()->where('movement_type', 'stock_adjustment')->count());
    }

    public function test_module_five_permission_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
    }

    private function productionOutput(string $batchNumber, string $expiryDate): ProductionOutput
    {
        $variant = ProductVariant::query()->firstOrFail();
        $unit = MeasurementUnit::query()->where('symbol', 'kg')->firstOrFail();
        $recipe = Recipe::query()->create([
            'product_variant_id' => $variant->id,
            'version' => 1,
            'status' => 'approved',
            'output_unit_id' => $unit->id,
        ]);
        $batch = ProductionBatch::query()->create([
            'batch_number' => $batchNumber,
            'product_variant_id' => $variant->id,
            'recipe_id' => $recipe->id,
            'production_date' => '2026-08-20',
            'expiry_date' => $expiryDate,
            'planned_quantity' => '12',
            'actual_output_quantity' => '12',
            'estimated_unit_cost' => '1.5000',
            'status' => 'completed',
        ]);

        return ProductionOutput::query()->create([
            'production_batch_id' => $batch->id,
            'product_variant_id' => $variant->id,
            'accepted_quantity' => '12',
            'rejected_quantity' => '0',
            'estimated_unit_cost' => '1.5000',
            'status' => 'pending_inventory',
        ]);
    }

    private function finishedBatch(ProductVariant $variant, string $batchNumber, string $expiryDate, string $quantity): FinishedProductBatch
    {
        return FinishedProductBatch::query()->create([
            'product_variant_id' => $variant->id,
            'batch_number' => $batchNumber,
            'production_date' => '2026-08-20',
            'expiry_date' => $expiryDate,
            'quantity_received' => $quantity,
            'quantity_available' => $quantity,
            'unit_cost' => '1.5000',
            'status' => 'available',
        ]);
    }

    private function materialBatch(string $quantity): MaterialBatch
    {
        $unit = MeasurementUnit::query()->where('symbol', 'kg')->firstOrFail();
        $material = Material::query()->create([
            'material_category_id' => MaterialCategory::query()->where('type', 'raw_material')->firstOrFail()->id,
            'base_unit_id' => $unit->id,
            'name' => 'Configurable Sugar',
            'status' => 'active',
        ]);
        $supplier = Supplier::query()->create(['name' => 'Configurable Supplier', 'status' => 'active']);
        $purchase = Purchase::query()->create([
            'purchase_number' => 'PUR-INV-TEST',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-08-20',
            'status' => 'received',
        ]);
        $item = $purchase->items()->create([
            'material_id' => $material->id,
            'unit_id' => $unit->id,
            'quantity' => $quantity,
            'unit_cost' => '2.0000',
            'line_total' => '0.00',
        ]);

        return MaterialBatch::query()->create([
            'material_id' => $material->id,
            'supplier_id' => $supplier->id,
            'purchase_item_id' => $item->id,
            'received_date' => '2026-08-20',
            'quantity_received' => $quantity,
            'quantity_available' => $quantity,
            'unit_cost' => '2.0000',
            'status' => 'available',
        ]);
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
