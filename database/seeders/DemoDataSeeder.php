<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\SaleType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CreditService;
use App\Services\ExpenseService;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\RecipeProductionService;
use App\Services\SalesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const MARKER = 'IVY_DEMO_DATA';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('Demo data seeding is disabled in production.');

            return;
        }

        if ($this->shouldSkip()) {
            $this->command?->warn('Demo data already exists. Nothing new was added.');

            return;
        }

        $actor = User::query()->whereHas('roles', fn ($query) => $query->where('is_owner', true))->first()
            ?? User::query()->firstOrFail();

        DB::transaction(function () use ($actor): void {
            $units = $this->units();
            $materials = $this->materials($units);
            $supplier = $this->supplier();
            $variants = $this->products();
            $customers = $this->customers();

            $this->purchaseStock($supplier, $materials, $units, $actor);
            $this->recipesAndProduction($variants, $materials, $units, $actor);
            $this->sales($variants, $customers, $actor);
            $this->expenses($actor);
        });

        $this->command?->info('Demo data added for local presentation.');
    }

    private function shouldSkip(): bool
    {
        return Sale::query()->where('notes', self::MARKER)->exists()
            || Expense::query()->where('description', 'like', '%'.self::MARKER.'%')->exists();
    }

    /**
     * @return array<string, MeasurementUnit>
     */
    private function units(): array
    {
        return [
            'kg' => MeasurementUnit::query()->where('symbol', 'kg')->firstOrFail(),
            'L' => MeasurementUnit::query()->where('symbol', 'L')->firstOrFail(),
            'pcs' => MeasurementUnit::query()->where('symbol', 'pcs')->firstOrFail(),
        ];
    }

    /**
     * @param  array<string, MeasurementUnit>  $units
     * @return array<string, Material>
     */
    private function materials(array $units): array
    {
        $raw = MaterialCategory::query()->where('type', 'raw_material')->firstOrFail();
        $packaging = MaterialCategory::query()->where('type', 'packaging_material')->firstOrFail();

        return [
            'pineapple' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-PINEAPPLE'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Pineapple Fruit',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '25.000',
                    'notes' => self::MARKER,
                ]
            ),
            'mango' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-MANGO'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Mango Pulp',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '20.000',
                    'notes' => self::MARKER,
                ]
            ),
            'milk' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-MILK'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['L']->id,
                    'name' => 'Demo Fresh Milk',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '30.000',
                    'notes' => self::MARKER,
                ]
            ),
            'sugar' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-SUGAR'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Sugar',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => false,
                    'reorder_level' => '15.000',
                    'notes' => self::MARKER,
                ]
            ),
            'bottle500' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-PK-BOTTLE-500'],
                [
                    'material_category_id' => $packaging->id,
                    'base_unit_id' => $units['pcs']->id,
                    'name' => 'Demo 500ml Bottle',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => false,
                    'reorder_level' => '100.000',
                    'notes' => self::MARKER,
                ]
            ),
        ];
    }

    private function supplier(): Supplier
    {
        return Supplier::query()->updateOrCreate(
            ['email' => 'demo-supplier@ivys-food.test'],
            [
                'name' => 'Demo Fresh Farms Supplier',
                'contact_person' => 'Demo Supplier Contact',
                'phone' => '0241112222',
                'address' => 'Accra, Ghana',
                'status' => 'active',
                'notes' => self::MARKER,
            ]
        );
    }

    /**
     * @return array<string, ProductVariant>
     */
    private function products(): array
    {
        $definitions = [
            'pineapple' => ['Pineapple 500ml', 'DEMO-JUICE-PINE-500', '7.00', '6.00', '5.50', '80.000', 14],
            'mango' => ['Mango 500ml', 'DEMO-JUICE-MANGO-500', '8.00', '7.00', '6.50', '60.000', 14],
            'vanilla' => ['Vanilla 500ml', 'DEMO-YOG-VAN-500', '10.00', '9.00', '8.00', '50.000', 10],
        ];

        return collect($definitions)
            ->mapWithKeys(function (array $data, string $key): array {
                [$name, $sku, $retail, $wholesale, $distributor, $reorder, $shelfLife] = $data;
                $variant = ProductVariant::query()->where('name', $name)->firstOrFail();
                $variant->update([
                    'sku' => $sku,
                    'barcode' => 'DEMO'.Str::upper(Str::substr($key, 0, 3)).'500',
                    'retail_price' => $retail,
                    'wholesale_price' => $wholesale,
                    'distributor_price' => $distributor,
                    'reorder_level' => $reorder,
                    'shelf_life_days' => $shelfLife,
                    'status' => 'active',
                ]);

                return [$key => $variant->fresh()];
            })
            ->all();
    }

    /**
     * @return array<string, Customer>
     */
    private function customers(): array
    {
        $shop = CustomerType::query()->where('slug', 'shop')->firstOrFail();
        $distributor = CustomerType::query()->where('slug', 'distributor')->firstOrFail();
        $retail = SaleType::query()->where('slug', 'retail')->firstOrFail();
        $wholesale = SaleType::query()->where('slug', 'wholesale')->firstOrFail();
        $distributorSale = SaleType::query()->where('slug', 'distributor')->firstOrFail();

        return [
            'walkin' => Customer::query()->updateOrCreate(
                ['normalized_phone' => '233240001001'],
                [
                    'customer_type_id' => $shop->id,
                    'default_sale_type_id' => $retail->id,
                    'name' => 'Demo Walk-in Shop',
                    'contact_person' => 'Ama Demo',
                    'phone' => '0240001001',
                    'email' => 'demo-walkin@ivys-food.test',
                    'location' => 'Madina',
                    'address' => 'Madina market area',
                    'status' => 'active',
                    'credit_limit' => '300.00',
                    'outstanding_balance' => '0.00',
                    'communication_consent' => true,
                    'notes' => self::MARKER,
                ]
            ),
            'shop' => Customer::query()->updateOrCreate(
                ['normalized_phone' => '233240001002'],
                [
                    'customer_type_id' => $shop->id,
                    'default_sale_type_id' => $wholesale->id,
                    'name' => 'Demo Adenta Mini Mart',
                    'contact_person' => 'Kojo Demo',
                    'phone' => '0240001002',
                    'email' => 'demo-shop@ivys-food.test',
                    'location' => 'Adenta',
                    'address' => 'Adenta main road',
                    'status' => 'active',
                    'credit_limit' => '500.00',
                    'outstanding_balance' => '0.00',
                    'communication_consent' => true,
                    'notes' => self::MARKER,
                ]
            ),
            'distributor' => Customer::query()->updateOrCreate(
                ['normalized_phone' => '233240001003'],
                [
                    'customer_type_id' => $distributor->id,
                    'default_sale_type_id' => $distributorSale->id,
                    'name' => 'Demo Greater Accra Distributor',
                    'contact_person' => 'Esi Demo',
                    'phone' => '0240001003',
                    'email' => 'demo-distributor@ivys-food.test',
                    'location' => 'Tema',
                    'address' => 'Tema Community 1',
                    'status' => 'active',
                    'credit_limit' => '1000.00',
                    'outstanding_balance' => '0.00',
                    'communication_consent' => true,
                    'notes' => self::MARKER,
                ]
            ),
        ];
    }

    /**
     * @param  array<string, Material>  $materials
     * @param  array<string, MeasurementUnit>  $units
     */
    private function purchaseStock(Supplier $supplier, array $materials, array $units, User $actor): void
    {
        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->subDays(3)->toDateString(),
            'notes' => self::MARKER,
            'items' => [
                ['material_id' => $materials['pineapple']->id, 'unit_id' => $units['kg']->id, 'quantity' => '150', 'unit_cost' => '5.5000'],
                ['material_id' => $materials['mango']->id, 'unit_id' => $units['kg']->id, 'quantity' => '120', 'unit_cost' => '7.0000'],
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'quantity' => '180', 'unit_cost' => '4.0000'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'quantity' => '80', 'unit_cost' => '6.5000'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'quantity' => '600', 'unit_cost' => '0.8000'],
            ],
        ], $actor);

        app(PurchaseService::class)->receive($purchase, [
            'received_date' => now()->subDays(2)->toDateString(),
            'batches' => $purchase->items->map(fn ($item): array => [
                'purchase_item_id' => $item->id,
                'lot_number' => 'DEMO-LOT-'.$item->id,
                'expiry_date' => now()->addMonths(4)->toDateString(),
            ])->all(),
        ], $actor);
    }

    /**
     * @param  array<string, ProductVariant>  $variants
     * @param  array<string, Material>  $materials
     * @param  array<string, MeasurementUnit>  $units
     */
    private function recipesAndProduction(array $variants, array $materials, array $units, User $actor): void
    {
        $recipes = [
            'pineapple' => [
                'variant' => $variants['pineapple'],
                'materials' => [
                    ['material_id' => $materials['pineapple']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '30'],
                    ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                    ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
                ],
            ],
            'mango' => [
                'variant' => $variants['mango'],
                'materials' => [
                    ['material_id' => $materials['mango']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '35'],
                    ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '4'],
                    ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
                ],
            ],
            'vanilla' => [
                'variant' => $variants['vanilla'],
                'materials' => [
                    ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'usage_type' => 'ingredient', 'quantity' => '80'],
                    ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '6'],
                    ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
                ],
            ],
        ];

        foreach ($recipes as $key => $definition) {
            $recipe = Recipe::query()
                ->where('product_variant_id', $definition['variant']->id)
                ->where('notes', self::MARKER)
                ->first();

            if (! $recipe) {
                $recipe = app(RecipeProductionService::class)->createRecipe([
                    'product_variant_id' => $definition['variant']->id,
                    'expected_output_quantity' => '100',
                    'output_unit_id' => $units['pcs']->id,
                    'notes' => self::MARKER,
                    'materials' => $definition['materials'],
                ], $actor);
                $recipe = app(RecipeProductionService::class)->approveRecipe($recipe, $actor);
            }

            $batch = app(RecipeProductionService::class)->createBatch([
                'recipe_id' => $recipe->id,
                'production_date' => now()->subDay()->toDateString(),
                'expiry_date' => now()->addDays($key === 'vanilla' ? 10 : 14)->toDateString(),
                'planned_quantity' => '100',
                'notes' => self::MARKER,
            ], $actor);

            $batch = app(RecipeProductionService::class)->completeBatch($batch, [
                'actual_output_quantity' => $key === 'vanilla' ? '92' : '96',
                'rejected_quantity' => '2',
                'wastage_quantity' => '2',
            ], $actor);

            app(InventoryService::class)->postProductionOutput($batch->output, $actor);
        }
    }

    /**
     * @param  array<string, ProductVariant>  $variants
     * @param  array<string, Customer>  $customers
     */
    private function sales(array $variants, array $customers, User $actor): void
    {
        $retail = SaleType::query()->where('slug', 'retail')->firstOrFail();
        $wholesale = SaleType::query()->where('slug', 'wholesale')->firstOrFail();
        $distributor = SaleType::query()->where('slug', 'distributor')->firstOrFail();

        app(SalesService::class)->createSale([
            'customer_id' => $customers['walkin']->id,
            'sale_type_id' => $retail->id,
            'sale_date' => now()->subHours(4)->format('Y-m-d H:i:s'),
            'notes' => self::MARKER,
            'items' => [
                ['product_variant_id' => $variants['pineapple']->id, 'quantity' => '6', 'discount_amount' => '0'],
                ['product_variant_id' => $variants['mango']->id, 'quantity' => '4', 'discount_amount' => '0'],
            ],
            'payments' => [
                ['payment_method' => 'Cash', 'amount' => '74.00'],
            ],
        ], $actor);

        app(SalesService::class)->createSale([
            'customer_id' => $customers['shop']->id,
            'sale_type_id' => $wholesale->id,
            'sale_date' => now()->subHours(2)->format('Y-m-d H:i:s'),
            'notes' => self::MARKER,
            'items' => [
                ['product_variant_id' => $variants['pineapple']->id, 'quantity' => '10', 'discount_amount' => '0'],
                ['product_variant_id' => $variants['vanilla']->id, 'quantity' => '5', 'discount_amount' => '0'],
            ],
            'payments' => [
                ['payment_method' => 'MoMo', 'amount' => '105.00', 'transaction_reference' => 'DEMO-MOMO-001'],
            ],
        ], $actor);

        app(SalesService::class)->createSale([
            'customer_id' => $customers['distributor']->id,
            'sale_type_id' => $distributor->id,
            'sale_date' => now()->subHour()->format('Y-m-d H:i:s'),
            'notes' => self::MARKER,
            'items' => [
                ['product_variant_id' => $variants['mango']->id, 'quantity' => '12', 'discount_amount' => '0'],
                ['product_variant_id' => $variants['vanilla']->id, 'quantity' => '8', 'discount_amount' => '0'],
            ],
            'payments' => [
                ['payment_method' => 'Credit', 'amount' => '142.00'],
            ],
        ], $actor);

        app(CreditService::class)->recordPayment($customers['distributor']->fresh(), [
            'payment_method' => 'MoMo',
            'amount' => '40.00',
            'transaction_reference' => 'DEMO-CREDIT-PAY-001',
            'received_at' => now(),
        ], $actor);
    }

    private function expenses(User $actor): void
    {
        $category = ExpenseCategory::query()->updateOrCreate(
            ['slug' => 'demo-fuel-and-delivery'],
            [
                'name' => 'Demo Fuel and Delivery',
                'description' => 'Demo delivery and transport costs.',
                'is_active' => true,
            ]
        );

        $expense = app(ExpenseService::class)->createExpense([
            'expense_category_id' => $category->id,
            'incurred_on' => now()->toDateString(),
            'payment_method' => 'Cash',
            'amount' => '85.00',
            'description' => self::MARKER.' - Fuel for morning deliveries',
            'receipt_original_name' => 'demo-fuel-receipt.jpg',
            'receipt_path' => 'demo/local-only/fuel-receipt.jpg',
        ], $actor);

        app(ExpenseService::class)->approve($expense, $actor);
    }
}
