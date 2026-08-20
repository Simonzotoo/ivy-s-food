<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\ProductSize;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Module2ProductCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingsSeeder::class);
        $this->seed(ProductCatalogueSeeder::class);
    }

    public function test_confirmed_catalogue_baseline_is_seeded_as_draft_variants(): void
    {
        $this->assertSame(3, ProductCategory::query()->count());
        $this->assertSame(29, ProductType::query()->count());
        $this->assertSame(13, ProductSize::query()->count());
        $this->assertSame(133, ProductVariant::query()->count());
        $this->assertSame(133, ProductVariant::query()->where('status', 'draft')->count());

        $this->assertDatabaseHas('product_types', ['name' => 'Pineginbeet']);
        $this->assertDatabaseHas('product_types', ['name' => 'Greek Plus Coconut Unsweetened']);
        $this->assertDatabaseHas('product_sizes', ['label' => '1000g']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Pineapple 250ml', 'sku' => null, 'barcode' => null]);
    }

    public function test_catalogue_requires_authentication_and_permission(): void
    {
        $this->get('/catalogue')->assertRedirect(route('login'));

        $auditor = $this->userWithRole('Auditor');
        $this->actingAs($auditor)->get('/catalogue')->assertForbidden();

        $inventory = $this->userWithRole('Inventory Officer');
        $this->actingAs($inventory)->get('/catalogue')->assertOk()->assertSee('Products and Sizes');
    }

    public function test_unauthorized_user_cannot_edit_variant(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $variant = ProductVariant::query()->firstOrFail();

        $this->actingAs($cashier)->get(route('catalogue.variants.edit', $variant))->assertForbidden();
        $this->actingAs($cashier)->put(route('catalogue.variants.update', $variant), [
            'sku' => 'SKU-001',
            'status' => 'draft',
        ])->assertForbidden();
    }

    public function test_authorized_user_can_update_variant_and_audit_is_recorded(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $variant = ProductVariant::query()->where('name', 'Pineapple 250ml')->firstOrFail();

        $this->actingAs($inventory)->put(route('catalogue.variants.update', $variant), [
            'sku' => 'JUI-PINEAPPLE-250',
            'barcode' => '1234567890123',
            'retail_price' => '10.00',
            'wholesale_price' => '9.00',
            'distributor_price' => '8.00',
            'status' => 'active',
            'reorder_level' => '20',
            'shelf_life_days' => '14',
        ])->assertRedirect(route('catalogue.index'));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'JUI-PINEAPPLE-250',
            'barcode' => '1234567890123',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'catalogue.variant_updated',
            'auditable_id' => $variant->id,
        ]);
    }

    public function test_incomplete_variant_cannot_be_activated(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $variant = ProductVariant::query()->firstOrFail();

        $this->actingAs($inventory)->patch(route('catalogue.variants.status', $variant), [
            'status' => 'active',
        ])->assertSessionHasErrors('status');

        $this->assertSame('draft', $variant->fresh()->status);
    }

    public function test_duplicate_sku_and_barcode_are_rejected(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $first = ProductVariant::query()->firstOrFail();
        $second = ProductVariant::query()->whereKeyNot($first->id)->firstOrFail();

        $first->update(['sku' => 'DUP-SKU', 'barcode' => 'DUP-BARCODE']);

        $this->actingAs($inventory)->put(route('catalogue.variants.update', $second), [
            'sku' => 'DUP-SKU',
            'barcode' => 'DUP-BARCODE',
            'status' => 'draft',
        ])->assertSessionHasErrors(['sku', 'barcode']);
    }

    public function test_catalogue_search_filter_and_export(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');

        $this->actingAs($inventory)->get('/catalogue?search=Pineapple&status=draft')
            ->assertOk()
            ->assertSee('Pineapple 250ml')
            ->assertDontSee('Vanilla 250ml');

        $this->actingAs($inventory)->get(route('catalogue.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('Pineapple 250ml');
    }

    public function test_catalogue_seeder_is_idempotent(): void
    {
        $counts = [
            ProductCategory::query()->count(),
            ProductType::query()->count(),
            ProductSize::query()->count(),
            ProductVariant::query()->count(),
        ];

        $this->seed(ProductCatalogueSeeder::class);

        $this->assertSame($counts, [
            ProductCategory::query()->count(),
            ProductType::query()->count(),
            ProductSize::query()->count(),
            ProductVariant::query()->count(),
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
