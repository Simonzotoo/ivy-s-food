<?php

namespace Tests\Feature;

use App\Mail\BroadcastMail;
use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use App\Models\Customer;
use App\Models\CustomerCommunication;
use App\Models\CustomerType;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleType;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\CustomerFoundationSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Module10BroadcastingTest extends TestCase
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

    public function test_broadcast_permissions_are_enforced(): void
    {
        $this->get('/broadcasts')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $marketing = $this->userWithRole('Marketing Officer');

        $this->actingAs($cashier)->get('/broadcasts')->assertForbidden();
        $this->actingAs($marketing)->get('/broadcasts')->assertOk();
    }

    public function test_preview_and_immediate_broadcast_use_consent_and_opt_out_filters(): void
    {
        Http::fake(['api.mnotify.com/*' => Http::response(['status' => 'success', 'code' => 2000, 'message' => 'ok'])]);

        $marketing = $this->userWithRole('Marketing Officer');
        $shop = CustomerType::query()->where('name', 'Shop')->firstOrFail();
        $retail = SaleType::query()->where('name', 'Retail')->firstOrFail();
        $eligible = $this->customer('Eligible Shop', $shop, $retail, true, null, 'Accra Central');
        $this->customer('No Consent Shop', $shop, $retail, false, null, 'Accra Central');
        $this->customer('Opted Out Shop', $shop, $retail, true, now(), 'Accra Central');

        $payload = [
            'name' => 'Accra shops',
            'channels' => ['sms'],
            'subject' => 'Stock update',
            'body' => 'Stock update.',
            'customer_type_id' => $shop->id,
            'sale_type_id' => $retail->id,
            'location' => 'Accra',
            'purchase_history' => 'any',
        ];

        $this->actingAs($marketing)->post('/broadcasts/preview', $payload)
            ->assertOk()
            ->assertSee('Eligible Shop')
            ->assertDontSee('No Consent Shop')
            ->assertDontSee('Opted Out Shop');

        $this->actingAs($marketing)->post('/broadcasts', $payload)->assertRedirect(route('broadcasts.create'));

        $broadcast = Broadcast::query()->firstOrFail();
        $recipient = BroadcastRecipient::query()->firstOrFail();
        $this->assertSame('sms', $broadcast->channel);
        $this->assertSame('sent', $broadcast->status);
        $this->assertSame($eligible->id, $recipient->customer_id);
        $this->assertSame('sent', $recipient->status);
        $this->assertTrue($recipient->provider_payload['success']);
        $this->assertSame(1, CustomerCommunication::query()->where('customer_id', $eligible->id)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'broadcast.sent']);
    }

    public function test_broadcast_can_send_via_sms_and_email_at_the_same_time(): void
    {
        Http::fake(['api.mnotify.com/*' => Http::response(['status' => 'success', 'code' => 2000, 'message' => 'ok'])]);
        Mail::fake();

        $marketing = $this->userWithRole('Marketing Officer');
        $shop = CustomerType::query()->where('name', 'Shop')->firstOrFail();
        $retail = SaleType::query()->where('name', 'Retail')->firstOrFail();
        $customer = $this->customer('Both Channels', $shop, $retail, true, null, 'Accra Central');
        $customer->update(['email' => 'both@example.com']);

        $this->actingAs($marketing)->post('/broadcasts', [
            'name' => 'Both channels',
            'channels' => ['sms', 'email'],
            'subject' => 'Promo',
            'body' => 'Special offer this week.',
            'customer_type_id' => $shop->id,
            'sale_type_id' => $retail->id,
            'purchase_history' => 'any',
        ])->assertRedirect(route('broadcasts.create'));

        $this->assertSame(2, Broadcast::query()->count());
        $this->assertDatabaseHas('broadcasts', ['channel' => 'sms', 'status' => 'sent']);
        $this->assertDatabaseHas('broadcasts', ['channel' => 'email', 'status' => 'sent']);
        $this->assertSame(2, CustomerCommunication::query()->where('customer_id', $customer->id)->count());
        Mail::assertSent(BroadcastMail::class, fn ($mail) => $mail->hasTo('both@example.com'));
    }

    public function test_email_broadcast_sends_via_mail_and_skips_customers_without_email(): void
    {
        Mail::fake();

        $marketing = $this->userWithRole('Marketing Officer');
        $shop = CustomerType::query()->where('name', 'Shop')->firstOrFail();
        $retail = SaleType::query()->where('name', 'Retail')->firstOrFail();
        $withEmail = $this->customer('Has Email', $shop, $retail, true, null, 'Accra Central');
        $withEmail->update(['email' => 'has-email@example.com']);
        $withoutEmail = $this->customer('No Email', $shop, $retail, true, null, 'Accra Central');

        $this->actingAs($marketing)->post('/broadcasts', [
            'name' => 'Email blast',
            'channels' => ['email'],
            'subject' => 'Promo',
            'body' => 'Special offer this week.',
            'customer_type_id' => $shop->id,
            'sale_type_id' => $retail->id,
            'purchase_history' => 'any',
        ])->assertRedirect(route('broadcasts.create'));

        $broadcast = Broadcast::query()->firstOrFail();
        $this->assertSame(1, $broadcast->total_logged);
        $this->assertSame(1, $broadcast->total_failed);
        $this->assertSame('sent', BroadcastRecipient::query()->where('customer_id', $withEmail->id)->firstOrFail()->status);
        $this->assertSame('failed', BroadcastRecipient::query()->where('customer_id', $withoutEmail->id)->firstOrFail()->status);
        Mail::assertSent(BroadcastMail::class, fn ($mail) => $mail->hasTo('has-email@example.com'));
    }

    public function test_scheduled_broadcast_is_processed_by_command_without_external_send(): void
    {
        Http::fake(['api.mnotify.com/*' => Http::response(['status' => 'success', 'code' => 2000, 'message' => 'ok'])]);

        $marketing = $this->userWithRole('Marketing Officer');
        $customer = $this->customer('Scheduled Customer', CustomerType::query()->firstOrFail(), SaleType::query()->firstOrFail(), true);

        $this->actingAs($marketing)->post('/broadcasts', [
            'name' => 'Scheduled sms',
            'channels' => ['sms'],
            'subject' => 'Scheduled',
            'body' => 'Scheduled message.',
            'scheduled_for' => now()->subMinute()->format('Y-m-d H:i:s'),
            'purchase_history' => 'any',
        ])->assertRedirect(route('broadcasts.create'));

        $broadcast = Broadcast::query()->firstOrFail();
        $this->assertSame('scheduled', $broadcast->status);
        $this->assertSame('pending', $broadcast->recipients()->firstOrFail()->status);

        $this->artisan('broadcasts:run-scheduled')->expectsOutput('1 scheduled broadcasts processed.')->assertExitCode(0);

        $this->assertSame('sent', $broadcast->fresh()->status);
        $this->assertTrue($broadcast->recipients()->firstOrFail()->provider_payload['success']);
        $this->assertSame(1, CustomerCommunication::query()->where('customer_id', $customer->id)->count());
    }

    public function test_purchase_history_segmentation_uses_sales_records(): void
    {
        $marketing = $this->userWithRole('Marketing Officer');
        $retail = SaleType::query()->firstOrFail();
        $buyer = $this->customer('Buyer', CustomerType::query()->firstOrFail(), $retail, true);
        $this->customer('No Purchase', CustomerType::query()->firstOrFail(), $retail, true);
        Sale::query()->create([
            'sale_number' => 'SALE-BCAST',
            'customer_id' => $buyer->id,
            'sale_type_id' => $retail->id,
            'sale_date' => now(),
            'subtotal' => '1.00',
            'total' => '1.00',
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($marketing)->post('/broadcasts/preview', [
            'name' => 'Purchase history',
            'channels' => ['sms'],
            'body' => 'Thank you.',
            'purchase_history' => 'has_purchases',
        ])->assertOk()->assertSee('Buyer')->assertDontSee('No Purchase');
    }

    public function test_customer_consent_checkbox_blocks_future_broadcasts(): void
    {
        $marketing = $this->userWithRole('Marketing Officer');
        $customer = $this->customer('Opt Out Candidate', CustomerType::query()->firstOrFail(), SaleType::query()->firstOrFail(), false);

        $this->actingAs($marketing)->post('/broadcasts', [
            'name' => 'Blocked',
            'channels' => ['sms'],
            'body' => 'Should not send.',
            'purchase_history' => 'any',
        ])->assertSessionHasErrors(['recipients']);

        $this->assertFalse($customer->fresh()->communication_consent);
    }

    public function test_module_ten_permission_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
    }

    private function customer(string $name, CustomerType $type, SaleType $saleType, bool $consent, mixed $optedOutAt = null, ?string $location = null): Customer
    {
        return Customer::query()->create([
            'customer_type_id' => $type->id,
            'default_sale_type_id' => $saleType->id,
            'name' => $name,
            'phone' => '024000'.random_int(1000, 9999),
            'normalized_phone' => '+23324000'.random_int(1000, 9999),
            'location' => $location,
            'status' => 'active',
            'communication_consent' => $consent,
            'communication_opted_out_at' => $optedOutAt,
        ]);
    }
}
