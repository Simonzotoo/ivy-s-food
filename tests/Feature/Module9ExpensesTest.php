<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Services\AuditService;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\CustomerFoundationSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Module9ExpensesTest extends TestCase
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

    public function test_expense_permissions_are_enforced(): void
    {
        $this->get('/expenses')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $accounts = $this->userWithRole('Accounts Officer');

        $this->actingAs($cashier)->get('/expenses')->assertForbidden();
        $this->actingAs($accounts)->get('/expenses')->assertOk();
        $this->actingAs($accounts)->get('/expenses/create')->assertOk();
        $this->actingAs($accounts)->get('/expenses/report')->assertOk();
    }

    public function test_category_and_expense_creation_are_validated_and_audited(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');

        $this->actingAs($accounts)->post('/expenses/categories', [
            'name' => 'Utilities',
            'description' => 'Configurable operating expense category.',
            'is_active' => '1',
        ])->assertRedirect();

        $category = ExpenseCategory::query()->where('name', 'Utilities')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'expense.category_created']);

        $this->actingAs($accounts)->post('/expenses', [
            'expense_category_id' => $category->id,
            'incurred_on' => '2026-08-20',
            'payment_method' => 'MoMo',
            'amount' => '125.50',
            'description' => 'Electricity prepaid top-up.',
            'receipt_original_name' => 'ecg-receipt.pdf',
            'receipt_path' => 'receipts/expenses/ecg-receipt.pdf',
        ])->assertRedirect();

        $expense = Expense::query()->firstOrFail();
        $this->assertSame('pending', $expense->status);
        $this->assertSame('125.50', (string) $expense->amount);
        $this->assertSame($accounts->id, $expense->created_by);
        $this->assertDatabaseHas('audit_logs', ['event' => 'expense.created']);
    }

    public function test_invalid_expense_payload_is_rejected(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $category = ExpenseCategory::query()->create(['name' => 'Transport', 'slug' => 'transport']);

        $this->actingAs($accounts)->post('/expenses', [
            'expense_category_id' => $category->id,
            'incurred_on' => '2026-08-20',
            'payment_method' => 'Cash',
            'amount' => '0',
            'description' => '',
        ])->assertSessionHasErrors(['amount', 'description']);
    }

    public function test_inactive_categories_cannot_be_used_for_live_expenses(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $category = ExpenseCategory::query()->create(['name' => 'Pending Category', 'slug' => 'pending-category', 'is_active' => false]);

        $this->actingAs($accounts)->post('/expenses', [
            'expense_category_id' => $category->id,
            'incurred_on' => '2026-08-20',
            'payment_method' => 'Cash',
            'amount' => '10.00',
            'description' => 'Should not be accepted.',
        ])->assertSessionHasErrors(['expense_category_id']);
    }

    public function test_approval_and_rejection_controls_are_audited(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $category = ExpenseCategory::query()->create(['name' => 'Fuel', 'slug' => 'fuel']);
        $approveExpense = $this->expense($category, 'EXP-APP');
        $rejectExpense = $this->expense($category, 'EXP-REJ');

        $this->actingAs($accounts)->post(route('expenses.approve', $approveExpense))->assertRedirect();
        $this->assertSame('approved', $approveExpense->fresh()->status);
        $this->assertSame($accounts->id, $approveExpense->fresh()->approved_by);

        $this->actingAs($accounts)->post(route('expenses.reject', $rejectExpense), [
            'rejection_reason' => 'Receipt does not match the amount.',
        ])->assertRedirect();
        $this->assertSame('rejected', $rejectExpense->fresh()->status);

        $this->assertDatabaseHas('audit_logs', ['event' => 'expense.approved']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'expense.rejected']);
    }

    public function test_expense_report_uses_only_approved_expenses(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $category = ExpenseCategory::query()->create(['name' => 'Maintenance', 'slug' => 'maintenance']);
        Expense::query()->create([
            'expense_number' => 'EXP-APPROVED',
            'expense_category_id' => $category->id,
            'incurred_on' => '2026-08-20',
            'payment_method' => 'Cash',
            'amount' => '50.00',
            'description' => 'Approved repair.',
            'status' => 'approved',
            'created_by' => $accounts->id,
        ]);
        Expense::query()->create([
            'expense_number' => 'EXP-PENDING',
            'expense_category_id' => $category->id,
            'incurred_on' => '2026-08-20',
            'payment_method' => 'Cash',
            'amount' => '75.00',
            'description' => 'Pending repair.',
            'status' => 'pending',
            'created_by' => $accounts->id,
        ]);

        $this->actingAs($accounts)->get('/expenses/report?from=2026-08-01&to=2026-08-31')
            ->assertOk()
            ->assertSee('GHS 50.00')
            ->assertSee('not a formal accounting statement');
    }

    public function test_expense_routes_are_inside_web_middleware_group(): void
    {
        $route = app('router')->getRoutes()->getByName('expenses.store');

        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('can:expenses.create', $route->gatherMiddleware());
    }

    public function test_module_nine_permission_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
    }

    public function test_audit_redacts_sensitive_expense_metadata(): void
    {
        $log = app(AuditService::class)->record('expense.redaction_check', metadata: [
            'receipt_token' => 'secret-token',
            'description' => 'safe',
        ]);

        $this->assertSame('[REDACTED]', AuditLog::query()->findOrFail($log->id)->metadata['receipt_token']);
        $this->assertSame('safe', AuditLog::query()->findOrFail($log->id)->metadata['description']);
    }

    private function expense(ExpenseCategory $category, string $number): Expense
    {
        return Expense::query()->create([
            'expense_number' => $number,
            'expense_category_id' => $category->id,
            'incurred_on' => '2026-08-20',
            'payment_method' => 'Cash',
            'amount' => '20.00',
            'description' => $number.' description.',
            'status' => 'pending',
        ]);
    }
}
