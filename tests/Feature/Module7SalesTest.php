<?php

namespace Tests\Feature;

use App\Models\CreditInvoice;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleReturn;
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

class Module7SalesTest extends TestCase
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

    public function test_sales_permissions_are_enforced(): void
    {
        $this->get('/sales')->assertRedirect(route('login'));

        $production = $this->userWithRole('Production Officer');
        $cashier = $this->userWithRole('Cashier / Sales Officer');

        $this->actingAs($production)->get('/sales')->assertForbidden();
        $this->actingAs($cashier)->get('/sales')->assertOk();
        $this->actingAs($cashier)->get('/sales/pos')->assertOk();
    }

    public function test_cash_sale_deducts_finished_stock_and_generates_documents(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $variant = $this->activeVariant();
        $batch = $this->finishedBatch($variant, 'FIN-SALE-001', '2026-09-30', '10');

        $this->actingAs($cashier)->post('/sales', $this->salePayload($variant, 'Retail', '3', [['payment_method' => 'Cash', 'amount' => '15']]))
            ->assertRedirect();

        $sale = Sale::query()->firstOrFail();
        $this->assertSame('15.00', (string) $sale->total);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('7.000', (string) $batch->fresh()->quantity_available);
        $this->assertDatabaseHas('finished_stock_movements', ['movement_type' => 'sale_deduction', 'quantity' => '-3.000']);
        $this->assertDatabaseHas('delivery_notes', ['sale_id' => $sale->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'sale.completed']);

        $this->actingAs($cashier)->get(route('sales.pdf', [$sale, 'receipt']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_credit_sale_requires_customer_and_creates_invoice_and_outstanding_balance(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $variant = $this->activeVariant();
        $this->finishedBatch($variant, 'FIN-CREDIT-001', '2026-09-30', '10');

        $this->actingAs($cashier)->post('/sales', $this->salePayload($variant, 'Retail', '2', [['payment_method' => 'Credit', 'amount' => '0']]))
            ->assertSessionHasErrors('customer_id');

        $customer = $this->customer();
        $this->actingAs($cashier)->post('/sales', $this->salePayload($variant, 'Retail', '2', [['payment_method' => 'Credit', 'amount' => '0']], $customer))
            ->assertRedirect();

        $sale = Sale::query()->firstOrFail();
        $this->assertSame('10.00', (string) $sale->balance_due);
        $this->assertSame('10.00', (string) $customer->fresh()->outstanding_balance);
        $this->assertSame(1, CreditInvoice::query()->count());
    }

    public function test_discount_requires_permission(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $owner = $this->userWithRole('Owner / Super Admin');
        $variant = $this->activeVariant();
        $this->finishedBatch($variant, 'FIN-DISC-001', '2026-09-30', '20');

        $this->actingAs($cashier)->post('/sales', $this->salePayload($variant, 'Retail', '1', [['payment_method' => 'Cash', 'amount' => '5']], null, '2'))
            ->assertRedirect();
        $this->assertSame('0.00', (string) Sale::query()->firstOrFail()->discount_total);

        $this->actingAs($owner)->post('/sales', $this->salePayload($variant, 'Retail', '1', [['payment_method' => 'Cash', 'amount' => '3']], null, '2'))
            ->assertRedirect();
        $this->assertSame('2.00', (string) Sale::query()->orderByDesc('id')->firstOrFail()->discount_total);
    }

    public function test_sale_blocks_insufficient_finished_stock(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $variant = $this->activeVariant();
        $this->finishedBatch($variant, 'FIN-LOW-001', '2026-09-30', '1');

        $this->actingAs($cashier)->post('/sales', $this->salePayload($variant, 'Retail', '2', [['payment_method' => 'Cash', 'amount' => '10']]))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Sale::query()->count());
    }

    public function test_sale_return_and_reversal_restore_stock_without_double_restoring_returned_sales(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $manager = $this->userWithRole('Manager');
        $variant = $this->activeVariant();
        $batch = $this->finishedBatch($variant, 'FIN-RET-001', '2026-09-30', '10');

        $this->actingAs($cashier)->post('/sales', $this->salePayload($variant, 'Retail', '4', [['payment_method' => 'Cash', 'amount' => '20']]))
            ->assertRedirect();
        $sale = Sale::query()->with('items')->firstOrFail();

        $this->actingAs($manager)->post(route('sales.returns.store', $sale), [
            'return_date' => '2026-08-20',
            'reason' => 'Customer returned sealed product',
            'items' => [
                ['sale_item_id' => $sale->items->first()->id, 'quantity' => '1'],
            ],
        ])->assertRedirect();

        $this->assertSame('7.000', (string) $batch->fresh()->quantity_available);
        $this->assertSame(1, SaleReturn::query()->count());

        $this->actingAs($manager)->post(route('sales.reverse', $sale), [
            'reason' => 'Incorrect sale captured',
        ])->assertSessionHasErrors('sale');

        $secondBatch = $this->finishedBatch($variant, 'FIN-REV-001', '2026-10-30', '10');
        $this->actingAs($cashier)->post('/sales', $this->salePayload($variant, 'Retail', '2', [['payment_method' => 'Cash', 'amount' => '10']]))
            ->assertRedirect();
        $secondSale = Sale::query()->orderByDesc('id')->firstOrFail();

        $this->actingAs($manager)->post(route('sales.reverse', $secondSale), [
            'reason' => 'Incorrect sale captured',
        ])->assertRedirect();

        $this->assertSame('10.000', (string) $secondBatch->fresh()->quantity_available);
        $this->assertSame('cancelled', $secondSale->fresh()->status);
        $this->assertSame(1, FinishedStockMovement::query()->where('movement_type', 'sale_reversal')->count());
    }

    public function test_module_seven_permission_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
    }

    private function activeVariant(): ProductVariant
    {
        $variant = ProductVariant::query()->firstOrFail();
        $variant->update([
            'sku' => 'SALE-TEST-SKU',
            'status' => 'active',
            'retail_price' => '5.00',
            'wholesale_price' => '4.00',
            'distributor_price' => '3.50',
        ]);

        return $variant->fresh();
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
            'unit_cost' => '1.0000',
            'status' => 'available',
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $payments
     * @return array<string, mixed>
     */
    private function salePayload(ProductVariant $variant, string $saleType, string $quantity, array $payments, ?Customer $customer = null, string $discount = '0'): array
    {
        return [
            'customer_id' => $customer?->id,
            'sale_type_id' => SaleType::query()->where('name', $saleType)->firstOrFail()->id,
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => $quantity, 'discount_amount' => $discount],
            ],
            'payments' => $payments,
        ];
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'customer_type_id' => CustomerType::query()->where('name', 'Shop')->firstOrFail()->id,
            'name' => 'Configurable Shop',
            'status' => 'active',
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
