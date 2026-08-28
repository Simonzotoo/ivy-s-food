<?php

namespace Tests\Feature;

use App\Models\CreditInvoice;
use App\Models\CreditReminder;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\CustomerType;
use App\Models\PaymentAllocation;
use App\Models\ReminderTemplate;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleType;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\CustomerFoundationSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Module8CreditTest extends TestCase
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

    public function test_credit_permissions_are_enforced(): void
    {
        $this->get('/credit/invoices')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $accounts = $this->userWithRole('Accounts Officer');

        $this->actingAs($cashier)->get('/credit/invoices')->assertForbidden();
        $this->actingAs($accounts)->get('/credit/invoices')->assertOk();
        $this->actingAs($accounts)->get('/credit/payments')->assertOk();
    }

    public function test_partial_payment_allocates_to_oldest_invoice_and_updates_balance(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $customer = $this->customer();
        $oldest = $this->invoice($customer, 'INV-OLD', '2026-08-01', '10.00');
        $this->invoice($customer, 'INV-NEW', '2026-09-01', '20.00');

        $this->actingAs($accounts)->post('/credit/payments', [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'amount' => '15.00',
        ])->assertRedirect(route('credit.payments'));

        $this->assertSame('0.00', (string) $oldest->fresh()->balance);
        $this->assertSame('paid', $oldest->fresh()->status);
        $this->assertSame('15.00', (string) $customer->fresh()->outstanding_balance);
        $this->assertSame(2, PaymentAllocation::query()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'credit.payment_recorded']);
    }

    public function test_payment_receipt_pdf_is_generated(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $customer = $this->customer();
        $this->invoice($customer, 'INV-PDF', '2026-08-30', '10.00');

        $this->actingAs($accounts)->post('/credit/payments', [
            'customer_id' => $customer->id,
            'payment_method' => 'MoMo',
            'amount' => '10.00',
            'transaction_reference' => 'MOMO-123',
        ])->assertRedirect();

        $payment = CustomerPayment::query()->firstOrFail();
        $this->actingAs($accounts)->get(route('credit.payments.receipt', $payment))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_aging_statuses_and_local_reminders_are_logged_without_external_send(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $customer = $this->customer();
        $invoice = $this->invoice($customer, 'INV-REM', '2026-08-01', '10.00');
        $template = ReminderTemplate::query()->create([
            'name' => 'Overdue Local',
            'channel' => 'local',
            'trigger_status' => 'overdue',
            'body' => 'Local overdue reminder.',
        ]);

        $this->actingAs($accounts)->get('/credit/aging')->assertOk();
        $this->assertSame('overdue', $invoice->fresh()->status);

        $this->actingAs($accounts)->post(route('credit.invoices.reminders', $invoice), [
            'reminder_template_id' => $template->id,
        ])->assertRedirect();

        $reminder = CreditReminder::query()->firstOrFail();
        $this->assertFalse($reminder->provider_payload['external_send']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'credit.reminder_logged']);
    }

    public function test_module_eight_permission_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'customer_type_id' => CustomerType::query()->where('name', 'Shop')->firstOrFail()->id,
            'name' => 'Credit Customer',
            'status' => 'active',
            'outstanding_balance' => '30.00',
        ]);
    }

    private function invoice(Customer $customer, string $number, string $dueDate, string $balance): CreditInvoice
    {
        $sale = Sale::query()->create([
            'sale_number' => 'SALE-'.$number,
            'customer_id' => $customer->id,
            'sale_type_id' => SaleType::query()->where('name', 'Retail')->firstOrFail()->id,
            'sale_date' => now(),
            'subtotal' => $balance,
            'total' => $balance,
            'balance_due' => $balance,
            'status' => 'completed',
            'payment_status' => 'unpaid',
        ]);

        return CreditInvoice::query()->create([
            'invoice_number' => $number,
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'issue_date' => '2026-08-01',
            'due_date' => $dueDate,
            'total' => $balance,
            'balance' => $balance,
            'status' => 'not_due',
        ]);
    }
}
