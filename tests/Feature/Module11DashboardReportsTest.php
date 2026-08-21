<?php

namespace Tests\Feature;

use App\Models\CreditInvoice;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleType;
use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\CustomerFoundationSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Module11DashboardReportsTest extends TestCase
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

    public function test_dashboard_and_report_permissions_are_enforced(): void
    {
        $this->get('/home')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $auditor = $this->userWithRole('Auditor');

        $this->actingAs($cashier)->get('/home')->assertOk();
        $this->actingAs($cashier)->get('/reports')->assertForbidden();
        $this->actingAs($auditor)->get('/reports')->assertOk();
    }

    public function test_dashboard_uses_actual_sales_credit_customer_and_expense_data(): void
    {
        $manager = $this->userWithRole('Manager');
        $this->operationalData();

        $this->actingAs($manager)->get('/home')
            ->assertOk()
            ->assertSee("Today's Sales", false)
            ->assertSee('GHS 25.00')
            ->assertSee('Customers Owing')
            ->assertSee('Quick actions')
            ->assertSee('GHS 12.00')
            ->assertSee("Today's Expenses", false);
    }

    public function test_reports_render_all_defined_report_types(): void
    {
        $auditor = $this->userWithRole('Auditor');
        $this->operationalData();

        foreach (array_keys(ReportService::REPORTS) as $type) {
            $this->actingAs($auditor)->get('/reports?type='.$type.'&from=2026-08-01&to=2026-08-31')
                ->assertOk()
                ->assertSee(ReportService::REPORTS[$type]);
        }
    }

    public function test_sales_report_filters_and_exports_work(): void
    {
        $auditor = $this->userWithRole('Auditor');
        $this->operationalData();

        $this->actingAs($auditor)->get('/reports?type=sales_by_product&from=2026-08-01&to=2026-08-31')
            ->assertOk()
            ->assertSee('Sales by product')
            ->assertSee('25.00');

        $this->actingAs($auditor)->get('/reports/export.csv?type=sales_by_product&from=2026-08-01&to=2026-08-31')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->actingAs($auditor)->get('/reports/export.pdf?type=sales_by_product&from=2026-08-01&to=2026-08-31')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_report_permission_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
    }

    private function operationalData(): void
    {
        $customer = Customer::query()->create([
            'customer_type_id' => CustomerType::query()->where('name', 'Shop')->firstOrFail()->id,
            'default_sale_type_id' => SaleType::query()->where('name', 'Retail')->firstOrFail()->id,
            'name' => 'Report Customer',
            'status' => 'active',
            'communication_consent' => true,
        ]);
        $variant = ProductVariant::query()->firstOrFail();
        $saleType = SaleType::query()->where('name', 'Retail')->firstOrFail();
        $sale = Sale::query()->create([
            'sale_number' => 'SALE-REPORT',
            'customer_id' => $customer->id,
            'sale_type_id' => $saleType->id,
            'sale_date' => now()->format('Y-m-d').' 10:00:00',
            'subtotal' => '25.00',
            'total' => '25.00',
            'amount_paid' => '13.00',
            'balance_due' => '12.00',
            'status' => 'completed',
            'payment_status' => 'partial',
        ]);
        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_variant_id' => $variant->id,
            'quantity' => '2.000',
            'unit_price' => '12.50',
            'line_total' => '25.00',
        ]);
        SalePayment::query()->create([
            'sale_id' => $sale->id,
            'payment_method' => 'Cash',
            'amount' => '13.00',
            'received_at' => now()->format('Y-m-d').' 10:02:00',
        ]);
        CreditInvoice::query()->create([
            'invoice_number' => 'INV-REPORT',
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'total' => '12.00',
            'balance' => '12.00',
            'status' => 'due_today',
        ]);
        $category = ExpenseCategory::query()->create(['name' => 'Report Expense', 'slug' => 'report-expense']);
        Expense::query()->create([
            'expense_number' => 'EXP-REPORT',
            'expense_category_id' => $category->id,
            'incurred_on' => now()->toDateString(),
            'payment_method' => 'Cash',
            'amount' => '4.00',
            'description' => 'Report expense.',
            'status' => 'approved',
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
