<?php

namespace Tests\Feature;

use App\Models\CreditInvoice;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\ImportBatch;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialStockMovement;
use App\Models\MeasurementUnit;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleType;
use App\Models\User;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\CustomerFoundationSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Module12ImportsTest extends TestCase
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
        $this->seed(CustomerFoundationSeeder::class);
    }

    public function test_import_permissions_are_enforced(): void
    {
        $this->get('/imports')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $inventory = $this->userWithRole('Inventory Officer');

        $this->actingAs($cashier)->get('/imports')->assertForbidden();
        $this->actingAs($inventory)->get('/imports')->assertOk();
    }

    public function test_template_downloads_are_available(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');

        $this->actingAs($inventory)->get('/imports/templates/prices')
            ->assertOk()
            ->assertSee('sku,retail_price,wholesale_price,distributor_price');
    }

    public function test_dry_run_records_errors_without_changing_data(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');

        $this->actingAs($inventory)->post('/imports', [
            'import_type' => 'barcodes',
            'mode' => 'dry_run',
            'csv_content' => "sku,barcode\nMISSING,12345",
        ])->assertRedirect();

        $batch = ImportBatch::query()->firstOrFail();
        $this->assertSame('dry_run_errors', $batch->status);
        $this->assertSame(1, $batch->error_rows);
        $this->assertDatabaseHas('audit_logs', ['event' => 'import.dry_run']);
    }

    public function test_price_and_barcode_import_updates_existing_variants(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $variant = ProductVariant::query()->firstOrFail();
        $variant->update(['sku' => 'PV-001']);

        $this->actingAs($inventory)->post('/imports', [
            'import_type' => 'prices',
            'mode' => 'apply',
            'csv_content' => "sku,retail_price,wholesale_price,distributor_price\nPV-001,10.00,9.00,8.00",
        ])->assertRedirect();

        $this->assertSame('10.00', (string) $variant->fresh()->retail_price);
        $this->assertDatabaseHas('audit_logs', ['event' => 'import.applied']);
    }

    public function test_opening_stock_import_creates_finished_batch_and_movement_not_direct_balance_edit(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $manager = $this->userWithRole('Manager');
        $variant = ProductVariant::query()->firstOrFail();
        $variant->update(['sku' => 'FIN-001']);

        $this->actingAs($inventory)->post('/imports', [
            'import_type' => 'opening_stock',
            'mode' => 'apply',
            'csv_content' => "stock_type,sku_or_name,batch_number,quantity,unit_cost,expiry_date\nfinished,FIN-001,OPEN-BATCH,5,2.50,2026-12-31",
        ])->assertRedirect();

        $batch = FinishedProductBatch::query()->where('batch_number', 'OPEN-BATCH')->firstOrFail();
        $this->assertSame('5.000', (string) $batch->quantity_available);
        $this->assertDatabaseHas('finished_stock_movements', ['movement_type' => 'opening_balance']);

        $this->actingAs($manager)->post(route('imports.rollback', ImportBatch::query()->firstOrFail()))->assertRedirect();
        $this->assertSame(0, FinishedProductBatch::query()->count());
        $this->assertSame(0, FinishedStockMovement::query()->count());
    }

    public function test_raw_material_and_open_credit_imports_are_applied_safely(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $unit = MeasurementUnit::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Kilogram', 'type' => 'weight']);
        $category = MaterialCategory::query()->firstOrCreate(['slug' => 'fruit'], ['name' => 'Fruit', 'type' => 'raw_material']);
        Material::query()->create(['name' => 'Imported Pineapple', 'sku' => 'RAW-PINE', 'material_category_id' => $category->id, 'base_unit_id' => $unit->id]);
        $customer = Customer::query()->create([
            'customer_type_id' => CustomerType::query()->firstOrFail()->id,
            'default_sale_type_id' => SaleType::query()->where('name', 'Retail')->firstOrFail()->id,
            'name' => 'Opening Credit Customer',
            'status' => 'active',
        ]);

        $this->actingAs($accounts)->post('/imports', [
            'import_type' => 'opening_stock',
            'mode' => 'apply',
            'csv_content' => "stock_type,sku_or_name,batch_number,quantity,unit_cost,expiry_date\nraw,RAW-PINE,RAW-OPEN,12,1.25,",
        ])->assertRedirect();
        $this->assertDatabaseHas('material_stock_movements', ['movement_type' => 'opening_balance']);

        $this->actingAs($accounts)->post('/imports', [
            'import_type' => 'open_credit_balances',
            'mode' => 'apply',
            'csv_content' => "customer_name,amount,due_date\nOpening Credit Customer,30.00,2026-09-20",
        ])->assertRedirect();

        $this->assertSame(1, CreditInvoice::query()->count());
        $this->assertSame('30.00', (string) $customer->fresh()->outstanding_balance);
        $this->assertSame(1, Sale::query()->where('notes', 'Opening credit balance import.')->count());
        $this->assertSame(1, MaterialStockMovement::query()->count());
    }

    public function test_module_twelve_permission_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
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
