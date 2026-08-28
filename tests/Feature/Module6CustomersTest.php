<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerCommunication;
use App\Models\CustomerType;
use App\Models\Sale;
use App\Models\SaleType;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\CustomerFoundationSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Module6CustomersTest extends TestCase
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

    public function test_customer_foundation_seeders_create_types_without_customers(): void
    {
        $this->assertDatabaseHas('customer_types', ['name' => 'Individual']);
        $this->assertDatabaseHas('customer_types', ['name' => 'Mall / Supermarket']);
        $this->assertDatabaseHas('sale_types', ['name' => 'Retail']);
        $this->assertDatabaseHas('sale_types', ['name' => 'Distributor']);
        $this->assertSame(0, Customer::query()->count());
    }

    public function test_customer_permissions_are_enforced(): void
    {
        $this->get('/customers')->assertRedirect(route('login'));

        $production = $this->userWithRole('Production Officer');
        $cashier = $this->userWithRole('Cashier / Sales Officer');

        $this->actingAs($production)->get('/customers')->assertForbidden();
        $this->actingAs($cashier)->get('/customers')->assertOk();
    }

    public function test_authorized_user_can_create_customer_with_normalized_phone_and_audit(): void
    {
        $manager = $this->userWithRole('Manager');
        $type = CustomerType::query()->where('name', 'Shop')->firstOrFail();
        $saleType = SaleType::query()->where('name', 'Wholesale')->firstOrFail();

        $this->actingAs($manager)->post('/customers', [
            'customer_type_id' => $type->id,
            'default_sale_type_id' => $saleType->id,
            'name' => 'Configurable Shop',
            'phone' => '024 000 0000',
            'email' => 'shop@example.test',
            'status' => 'active',
            'credit_limit' => '500.00',
            'communication_consent' => '1',
        ])->assertRedirect();

        $customer = Customer::query()->firstOrFail();
        $this->assertSame('+233240000000', $customer->normalized_phone);
        $this->assertSame('Shop', $customer->type->name);
        $this->assertSame('Wholesale', $customer->defaultSaleType->name);
        $this->assertSame('500.00', (string) $customer->credit_limit);
        $this->assertSame('0.00', (string) $customer->outstanding_balance);
        $this->assertDatabaseHas('audit_logs', ['event' => 'customer.created']);
    }

    public function test_duplicate_normalized_phone_is_rejected(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $type = CustomerType::query()->where('name', 'Individual')->firstOrFail();

        Customer::query()->create([
            'customer_type_id' => $type->id,
            'name' => 'Existing Customer',
            'phone' => '0240000000',
            'normalized_phone' => '+233240000000',
            'status' => 'active',
        ]);

        $this->actingAs($cashier)->post('/customers', [
            'customer_type_id' => $type->id,
            'name' => 'Duplicate Customer',
            'phone' => '+233240000000',
            'status' => 'active',
        ])->assertSessionHasErrors('phone');
    }

    public function test_user_without_credit_permission_cannot_set_credit_limit(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $type = CustomerType::query()->where('name', 'Individual')->firstOrFail();

        $this->actingAs($cashier)->post('/customers', [
            'customer_type_id' => $type->id,
            'name' => 'Retail Customer',
            'phone' => '0550000000',
            'status' => 'active',
            'credit_limit' => '9999.00',
        ])->assertRedirect();

        $this->assertNull(Customer::query()->firstOrFail()->credit_limit);
    }

    public function test_customer_communication_history_is_logged_with_audit(): void
    {
        $marketing = $this->userWithRole('Marketing Officer');
        $customer = Customer::query()->create([
            'customer_type_id' => CustomerType::query()->where('name', 'Individual')->firstOrFail()->id,
            'name' => 'Retail Customer',
            'status' => 'active',
        ]);

        $this->actingAs($marketing)->post(route('customers.communications.store', $customer), [
            'channel' => 'whatsapp',
            'direction' => 'outbound',
            'subject' => 'Price list follow-up',
            'message' => 'Local test only.',
        ])->assertRedirect();

        $this->assertSame(1, CustomerCommunication::query()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'customer.communication_logged']);
    }

    public function test_customer_show_lists_recent_sales_only_for_authorized_roles(): void
    {
        $manager = $this->userWithRole('Manager');
        $marketing = $this->userWithRole('Marketing Officer');
        $customer = Customer::query()->create([
            'customer_type_id' => CustomerType::query()->where('name', 'Shop')->firstOrFail()->id,
            'name' => 'History Shop',
            'status' => 'active',
        ]);
        $saleType = SaleType::query()->where('name', 'Retail')->firstOrFail();
        Sale::query()->create([
            'sale_number' => 'SALE-HIST-1',
            'customer_id' => $customer->id,
            'sale_type_id' => $saleType->id,
            'sale_date' => now(),
            'subtotal' => '20.00',
            'total' => '20.00',
            'balance_due' => '0.00',
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($manager)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Recent purchases')
            ->assertSee('SALE-HIST-1');

        // Marketing Officer can view the customer but has no sales.view permission,
        // so the purchase-history section (and the sale number within it) must not appear.
        $this->actingAs($marketing)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertDontSee('SALE-HIST-1');
    }

    public function test_module_six_seeders_are_idempotent(): void
    {
        $counts = [CustomerType::query()->count(), SaleType::query()->count()];

        $this->seed(CustomerFoundationSeeder::class);
        $this->seed(CustomerFoundationSeeder::class);

        $this->assertSame($counts, [CustomerType::query()->count(), SaleType::query()->count()]);
    }
}
