<?php

namespace Tests\Feature;

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
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Module4RecipesProductionTest extends TestCase
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

    public function test_recipe_and_production_permissions_are_enforced(): void
    {
        $this->get('/recipes')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $production = $this->userWithRole('Production Officer');

        $this->actingAs($cashier)->get('/recipes')->assertForbidden();
        $this->actingAs($production)->get('/recipes')->assertOk();
        $this->actingAs($production)->get('/production/batches')->assertOk();
    }

    public function test_authorized_users_can_create_and_approve_recipe_with_audit(): void
    {
        $production = $this->userWithRole('Production Officer');
        $manager = $this->userWithRole('Manager');
        [$variant, $material, $unit] = $this->variantMaterialAndUnit();

        $this->actingAs($production)->post('/recipes', [
            'product_variant_id' => $variant->id,
            'expected_output_quantity' => '10',
            'output_unit_id' => $unit->id,
            'materials' => [
                ['material_id' => $material->id, 'unit_id' => $unit->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
            ],
        ])->assertRedirect();

        $recipe = Recipe::query()->firstOrFail();
        $this->assertSame('draft', $recipe->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'recipe.created']);

        $this->actingAs($manager)->post(route('recipes.approve', $recipe))->assertRedirect();

        $this->assertSame('approved', $recipe->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'recipe.approved']);
    }

    public function test_production_batch_requires_approved_recipe(): void
    {
        $production = $this->userWithRole('Production Officer');
        [$variant, $material, $unit] = $this->variantMaterialAndUnit();
        $recipe = Recipe::query()->create([
            'product_variant_id' => $variant->id,
            'version' => 1,
            'status' => 'draft',
            'expected_output_quantity' => '10',
            'output_unit_id' => $unit->id,
        ]);
        $recipe->materials()->create([
            'material_id' => $material->id,
            'unit_id' => $unit->id,
            'usage_type' => 'ingredient',
            'quantity' => '5',
        ]);

        $this->actingAs($production)->post('/production/batches', [
            'recipe_id' => $recipe->id,
            'production_date' => '2026-08-20',
            'planned_quantity' => '10',
        ])->assertSessionHasErrors('recipe_id');
    }

    public function test_completion_consumes_material_stock_and_records_output_cost_and_audit(): void
    {
        $production = $this->userWithRole('Production Officer');
        [$variant, $material, $unit] = $this->variantMaterialAndUnit();
        $batch = $this->approvedRecipeBatch($variant, $material, $unit);
        $materialBatch = $this->materialBatch($material, $unit, '25', '2.0000');

        $this->actingAs($production)->post(route('production.batches.complete', $batch), [
            'actual_output_quantity' => '9',
            'rejected_quantity' => '1',
            'wastage_quantity' => '0.5',
        ])->assertRedirect();

        $this->assertSame('20.000', (string) $materialBatch->fresh()->quantity_available);
        $this->assertDatabaseHas('material_stock_movements', ['movement_type' => 'production_usage', 'quantity' => '-5.000']);
        $this->assertSame('completed', $batch->fresh()->status);
        $this->assertSame('10.00', (string) $batch->fresh()->estimated_batch_cost);
        $this->assertSame('1.1111', (string) $batch->fresh()->estimated_unit_cost);
        $this->assertSame('90.00', (string) $batch->fresh()->yield_percent);
        $this->assertSame(1, ProductionOutput::query()->where('status', 'pending_inventory')->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'production_batch.completed']);
    }

    public function test_completion_blocks_insufficient_material_stock(): void
    {
        $production = $this->userWithRole('Production Officer');
        [$variant, $material, $unit] = $this->variantMaterialAndUnit();
        $batch = $this->approvedRecipeBatch($variant, $material, $unit);
        $this->materialBatch($material, $unit, '2', '2.0000');

        $this->actingAs($production)->post(route('production.batches.complete', $batch), [
            'actual_output_quantity' => '9',
        ])->assertSessionHasErrors('materials');

        $this->assertSame(0, MaterialStockMovement::query()->where('movement_type', 'production_usage')->count());
        $this->assertSame('draft', $batch->fresh()->status);
    }

    public function test_completed_batch_can_be_reversed_and_restores_material_stock(): void
    {
        $production = $this->userWithRole('Production Officer');
        $owner = $this->userWithRole('Owner / Super Admin');
        [$variant, $material, $unit] = $this->variantMaterialAndUnit();
        $batch = $this->approvedRecipeBatch($variant, $material, $unit);
        $materialBatch = $this->materialBatch($material, $unit, '25', '2.0000');

        $this->actingAs($production)->post(route('production.batches.complete', $batch), [
            'actual_output_quantity' => '10',
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('production.batches.reverse', $batch->fresh()), [
            'reason' => 'Incorrect production date',
        ])->assertRedirect();

        $this->assertSame('25.000', (string) $materialBatch->fresh()->quantity_available);
        $this->assertSame('reversed', $batch->fresh()->status);
        $this->assertDatabaseHas('material_stock_movements', ['movement_type' => 'production_reversal', 'quantity' => '5.000']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'production_batch.reversed']);
    }

    public function test_module_four_permissions_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
    }

    /**
     * @return array{0: ProductVariant, 1: Material, 2: MeasurementUnit}
     */
    private function variantMaterialAndUnit(): array
    {
        $variant = ProductVariant::query()->firstOrFail();
        $unit = MeasurementUnit::query()->where('symbol', 'kg')->firstOrFail();
        $material = Material::query()->first() ?? Material::query()->create([
            'material_category_id' => MaterialCategory::query()->where('type', 'raw_material')->firstOrFail()->id,
            'base_unit_id' => $unit->id,
            'name' => 'Configurable Sugar',
            'status' => 'active',
        ]);

        return [$variant, $material, $unit];
    }

    private function approvedRecipeBatch(ProductVariant $variant, Material $material, MeasurementUnit $unit): ProductionBatch
    {
        $recipe = Recipe::query()->create([
            'product_variant_id' => $variant->id,
            'version' => 1,
            'status' => 'approved',
            'expected_output_quantity' => '10',
            'output_unit_id' => $unit->id,
            'approved_at' => now(),
        ]);
        $recipe->materials()->create([
            'material_id' => $material->id,
            'unit_id' => $unit->id,
            'usage_type' => 'ingredient',
            'quantity' => '5',
        ]);

        return ProductionBatch::query()->create([
            'batch_number' => 'PROD-TEST-001',
            'product_variant_id' => $variant->id,
            'recipe_id' => $recipe->id,
            'production_date' => '2026-08-20',
            'planned_quantity' => '10',
            'status' => 'draft',
        ]);
    }

    private function materialBatch(Material $material, MeasurementUnit $unit, string $quantity, string $unitCost): MaterialBatch
    {
        $supplier = Supplier::query()->create(['name' => 'Configurable Supplier', 'status' => 'active']);
        $purchase = Purchase::query()->create([
            'purchase_number' => 'PUR-PROD-TEST',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-08-20',
            'status' => 'received',
        ]);
        $item = $purchase->items()->create([
            'material_id' => $material->id,
            'unit_id' => $unit->id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'line_total' => '0.00',
        ]);

        return MaterialBatch::query()->create([
            'material_id' => $material->id,
            'supplier_id' => $supplier->id,
            'purchase_item_id' => $item->id,
            'received_date' => '2026-08-20',
            'quantity_received' => $quantity,
            'quantity_available' => $quantity,
            'unit_cost' => $unitCost,
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
