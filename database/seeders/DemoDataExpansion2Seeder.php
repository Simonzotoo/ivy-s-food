<?php

namespace Database\Seeders;

use App\Models\Customer;
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
use App\Services\BroadcastService;
use App\Services\ExpenseService;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\RecipeProductionService;
use App\Services\SalesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataExpansion2Seeder extends Seeder
{
    private const MARKER = 'IVY_DEMO_EXPANSION_2';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('Demo data seeding is disabled in production.');

            return;
        }

        if ($this->shouldSkip()) {
            $this->command?->warn('Demo expansion 2 data already exists. Nothing new was added.');

            return;
        }

        $actor = User::query()->whereHas('roles', fn ($query) => $query->where('is_owner', true))->first()
            ?? User::query()->firstOrFail();

        DB::transaction(function () use ($actor): void {
            $units = $this->units();
            $materials = $this->materials($units);
            $suppliers = $this->suppliers();
            $variants = $this->products();
            $customers = $this->existingCustomers();

            $recipes = [];
            $cycles = [14, 7];

            foreach ($cycles as $daysAgo) {
                $start = now()->subDays($daysAgo);
                $this->restock($suppliers, $materials, $units, $start, $actor);
                $recipes = $this->produce($variants, $materials, $units, $recipes, $start, $actor);
                $this->recentSales($variants, $customers, $start, $actor);
            }

            $this->recentExpenses($actor);
            $this->broadcastExample($actor);
        });

        $this->command?->info('Demo data expanded further: broader catalogue, more suppliers, fresher activity.');
    }

    private function shouldSkip(): bool
    {
        return Sale::query()->where('notes', self::MARKER)->exists();
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

        $existing = collect([
            'pineapple' => 'DEMO-RM-PINEAPPLE',
            'mango' => 'DEMO-RM-MANGO',
            'orange' => 'DEMO-RM-ORANGE',
            'milk' => 'DEMO-RM-MILK',
            'sugar' => 'DEMO-RM-SUGAR',
            'bottle500' => 'DEMO-PK-BOTTLE-500',
        ])->mapWithKeys(fn (string $sku, string $key): array => [$key => Material::query()->where('sku', $sku)->firstOrFail()]);

        $new = [
            'watermelon' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-WATERMELON'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Watermelon Puree',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '20.000',
                    'notes' => self::MARKER,
                ]
            ),
            'guava' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-GUAVA'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Guava Pulp',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '15.000',
                    'notes' => self::MARKER,
                ]
            ),
            'tigernut' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-TIGERNUT'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Tigernut Extract',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '20.000',
                    'notes' => self::MARKER,
                ]
            ),
            'banana' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-BANANA'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Banana Puree',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '15.000',
                    'notes' => self::MARKER,
                ]
            ),
            'wheat' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-WHEAT'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Wheat Bran Base',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => false,
                    'reorder_level' => '10.000',
                    'notes' => self::MARKER,
                ]
            ),
            'tub500' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-PK-TUB-500'],
                [
                    'material_category_id' => $packaging->id,
                    'base_unit_id' => $units['pcs']->id,
                    'name' => 'Demo 500g Tub',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => false,
                    'reorder_level' => '100.000',
                    'notes' => self::MARKER,
                ]
            ),
        ];

        return $existing->merge($new)->all();
    }

    /**
     * @return array<string, Supplier>
     */
    private function suppliers(): array
    {
        $definitions = [
            'volta' => ['Demo Volta Fruit Cooperative', 'Kwesi Volta', '0242220001', 'Ho', 'Ho, Volta Region'],
            'kumasidairy' => ['Demo Kumasi Dairy Supplies', 'Abena Dairy', '0242220002', 'Kumasi', 'Kumasi Central Market'],
            'accrapack' => ['Demo Accra Packaging Ltd', 'Kojo Packaging', '0242220003', 'Tema', 'Tema Industrial Area'],
        ];

        return collect($definitions)->mapWithKeys(function (array $data, string $key): array {
            [$name, $contact, $phone, $location, $address] = $data;

            return [$key => Supplier::query()->updateOrCreate(
                ['email' => 'demo-'.$key.'@ivys-food.test'],
                [
                    'name' => $name,
                    'contact_person' => $contact,
                    'phone' => $phone,
                    'location' => $location,
                    'address' => $address,
                    'status' => 'active',
                    'notes' => self::MARKER,
                ]
            )];
        })->all();
    }

    /**
     * @return array<string, ProductVariant>
     */
    private function products(): array
    {
        $existing = collect([
            'pineapple' => 'Pineapple 500ml',
            'mango' => 'Mango 500ml',
            'orange' => 'Orange 500ml',
        ])->mapWithKeys(fn (string $name, string $key): array => [$key => ProductVariant::query()->where('name', $name)->firstOrFail()]);

        $new = [
            'watermelon' => ['Watermelon 500ml', 'DEMO-JUICE-WATERMELON-500', '7.00', '6.00', '5.50', '60.000', 14],
            'mixedfruit' => ['Mixed Fruit 500ml', 'DEMO-JUICE-MIXED-500', '7.50', '6.50', '6.00', '60.000', 14],
            'tigernut' => ['Tigernut 500ml', 'DEMO-JUICE-TIGERNUT-500', '8.00', '7.00', '6.50', '50.000', 14],
            'guavapine' => ['Guavapine 500ml', 'DEMO-JUICE-GUAVAPINE-500', '7.50', '6.50', '6.00', '50.000', 14],
            'banana' => ['Banana 500ml', 'DEMO-YOG-BANANA-500', '10.00', '9.00', '8.00', '50.000', 10],
            'vanillawheat' => ['Vanilla Wheat 500ml', 'DEMO-YOG-VANWHEAT-500', '11.00', '10.00', '9.00', '40.000', 10],
            'greekunsweet' => ['Greek Yoghurt Unsweetened 500g', 'DEMO-GRK-UNSWEET-500', '15.00', '13.50', '12.00', '30.000', 14],
            'greeksweet' => ['Greek Yoghurt Sweetened 500g', 'DEMO-GRK-SWEET-500', '16.00', '14.50', '13.00', '30.000', 14],
        ];

        $newVariants = collect($new)->mapWithKeys(function (array $data, string $key): array {
            [$name, $sku, $retail, $wholesale, $distributor, $reorder, $shelfLife] = $data;
            $variant = ProductVariant::query()->where('name', $name)->firstOrFail();
            $variant->update([
                'sku' => $sku,
                'retail_price' => $retail,
                'wholesale_price' => $wholesale,
                'distributor_price' => $distributor,
                'reorder_level' => $reorder,
                'shelf_life_days' => $shelfLife,
                'status' => 'active',
            ]);

            return [$key => $variant->fresh()];
        });

        return $existing->merge($newVariants)->all();
    }

    /**
     * @return array<string, Customer>
     */
    private function existingCustomers(): array
    {
        $phones = [
            'walkin' => '233240001001',
            'shop' => '233240001002',
            'distributor' => '233240001003',
            'ama' => '233240001004',
            'kwame' => '233240001005',
            'spintex' => '233240001006',
            'dzorwulu' => '233240001007',
            'kumasi' => '233240001008',
            'palace' => '233240001009',
        ];

        return collect($phones)->mapWithKeys(fn (string $phone, string $key): array => [
            $key => Customer::query()->where('normalized_phone', $phone)->firstOrFail(),
        ])->all();
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, Material>  $materials
     * @param  array<string, MeasurementUnit>  $units
     */
    private function restock(array $suppliers, array $materials, array $units, Carbon $start, User $actor): void
    {
        $fruitPurchase = app(PurchaseService::class)->create([
            'supplier_id' => $suppliers['volta']->id,
            'purchase_date' => $start->toDateString(),
            'notes' => self::MARKER,
            'items' => [
                ['material_id' => $materials['watermelon']->id, 'unit_id' => $units['kg']->id, 'quantity' => '35', 'unit_cost' => '4.0000'],
                ['material_id' => $materials['guava']->id, 'unit_id' => $units['kg']->id, 'quantity' => '20', 'unit_cost' => '6.0000'],
                ['material_id' => $materials['tigernut']->id, 'unit_id' => $units['kg']->id, 'quantity' => '35', 'unit_cost' => '7.0000'],
                ['material_id' => $materials['banana']->id, 'unit_id' => $units['kg']->id, 'quantity' => '15', 'unit_cost' => '5.0000'],
                ['material_id' => $materials['pineapple']->id, 'unit_id' => $units['kg']->id, 'quantity' => '35', 'unit_cost' => '5.5000'],
                ['material_id' => $materials['mango']->id, 'unit_id' => $units['kg']->id, 'quantity' => '25', 'unit_cost' => '7.0000'],
                ['material_id' => $materials['orange']->id, 'unit_id' => $units['kg']->id, 'quantity' => '15', 'unit_cost' => '6.0000'],
            ],
        ], $actor);
        app(PurchaseService::class)->receive($fruitPurchase, [
            'received_date' => $start->copy()->addDay()->toDateString(),
            'batches' => $fruitPurchase->items->map(fn ($item): array => [
                'purchase_item_id' => $item->id,
                'lot_number' => 'DEMO2-LOT-'.$item->id,
                'expiry_date' => $start->copy()->addMonths(3)->toDateString(),
            ])->all(),
        ], $actor);

        $dairyPurchase = app(PurchaseService::class)->create([
            'supplier_id' => $suppliers['kumasidairy']->id,
            'purchase_date' => $start->toDateString(),
            'notes' => self::MARKER,
            'items' => [
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'quantity' => '400', 'unit_cost' => '4.0000'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'quantity' => '55', 'unit_cost' => '6.5000'],
                ['material_id' => $materials['wheat']->id, 'unit_id' => $units['kg']->id, 'quantity' => '20', 'unit_cost' => '4.5000'],
            ],
        ], $actor);
        app(PurchaseService::class)->receive($dairyPurchase, [
            'received_date' => $start->copy()->addDay()->toDateString(),
            'batches' => $dairyPurchase->items->map(fn ($item): array => [
                'purchase_item_id' => $item->id,
                'lot_number' => 'DEMO2-LOT-'.$item->id,
                'expiry_date' => $start->copy()->addMonths(3)->toDateString(),
            ])->all(),
        ], $actor);

        $packagingPurchase = app(PurchaseService::class)->create([
            'supplier_id' => $suppliers['accrapack']->id,
            'purchase_date' => $start->toDateString(),
            'notes' => self::MARKER,
            'items' => [
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'quantity' => '700', 'unit_cost' => '0.8000'],
                ['material_id' => $materials['tub500']->id, 'unit_id' => $units['pcs']->id, 'quantity' => '250', 'unit_cost' => '1.2000'],
            ],
        ], $actor);
        app(PurchaseService::class)->receive($packagingPurchase, [
            'received_date' => $start->copy()->addDay()->toDateString(),
            'batches' => $packagingPurchase->items->map(fn ($item): array => [
                'purchase_item_id' => $item->id,
                'lot_number' => 'DEMO2-LOT-'.$item->id,
                'expiry_date' => $start->copy()->addYear()->toDateString(),
            ])->all(),
        ], $actor);
    }

    /**
     * @param  array<string, ProductVariant>  $variants
     * @param  array<string, Material>  $materials
     * @param  array<string, MeasurementUnit>  $units
     * @param  array<string, Recipe>  $recipes
     * @return array<string, Recipe>
     */
    private function produce(array $variants, array $materials, array $units, array $recipes, Carbon $start, User $actor): array
    {
        $definitions = [
            'watermelon' => [
                ['material_id' => $materials['watermelon']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '30'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'mixedfruit' => [
                ['material_id' => $materials['pineapple']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '10'],
                ['material_id' => $materials['mango']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '10'],
                ['material_id' => $materials['orange']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '10'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'tigernut' => [
                ['material_id' => $materials['tigernut']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '30'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'guavapine' => [
                ['material_id' => $materials['guava']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '15'],
                ['material_id' => $materials['pineapple']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '15'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'banana' => [
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'usage_type' => 'ingredient', 'quantity' => '80'],
                ['material_id' => $materials['banana']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '10'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'vanillawheat' => [
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'usage_type' => 'ingredient', 'quantity' => '80'],
                ['material_id' => $materials['wheat']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '8'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '6'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'greekunsweet' => [
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'usage_type' => 'ingredient', 'quantity' => '100'],
                ['material_id' => $materials['tub500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'greeksweet' => [
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'usage_type' => 'ingredient', 'quantity' => '100'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '8'],
                ['material_id' => $materials['tub500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
        ];

        $shelfDays = [
            'watermelon' => 14, 'mixedfruit' => 14, 'tigernut' => 14, 'guavapine' => 14,
            'banana' => 10, 'vanillawheat' => 10, 'greekunsweet' => 14, 'greeksweet' => 14,
        ];

        foreach ($definitions as $key => $materialLines) {
            if (! isset($recipes[$key])) {
                $recipe = Recipe::query()
                    ->where('product_variant_id', $variants[$key]->id)
                    ->where('notes', self::MARKER)
                    ->first();

                if (! $recipe) {
                    $recipe = app(RecipeProductionService::class)->createRecipe([
                        'product_variant_id' => $variants[$key]->id,
                        'expected_output_quantity' => '100',
                        'output_unit_id' => $units['pcs']->id,
                        'notes' => self::MARKER,
                        'materials' => $materialLines,
                    ], $actor);
                    $recipe = app(RecipeProductionService::class)->approveRecipe($recipe, $actor);
                }

                $recipes[$key] = $recipe;
            }

            $batch = app(RecipeProductionService::class)->createBatch([
                'recipe_id' => $recipes[$key]->id,
                'production_date' => $start->copy()->addDays(2)->toDateString(),
                'expiry_date' => $start->copy()->addDays(2 + $shelfDays[$key])->toDateString(),
                'planned_quantity' => '100',
                'notes' => self::MARKER,
            ], $actor);

            $batch = app(RecipeProductionService::class)->completeBatch($batch, [
                'actual_output_quantity' => '95',
                'rejected_quantity' => '3',
                'wastage_quantity' => '2',
            ], $actor);

            app(InventoryService::class)->postProductionOutput($batch->output, $actor);
        }

        return $recipes;
    }

    /**
     * @param  array<string, ProductVariant>  $variants
     * @param  array<string, Customer>  $customers
     */
    private function recentSales(array $variants, array $customers, Carbon $start, User $actor): void
    {
        $retail = SaleType::query()->where('slug', 'retail')->firstOrFail();
        $wholesale = SaleType::query()->where('slug', 'wholesale')->firstOrFail();
        $distributorSale = SaleType::query()->where('slug', 'distributor')->firstOrFail();

        $customerKeys = array_keys($customers);
        $productKeys = array_keys($variants);
        $paymentMethods = ['Cash', 'MoMo'];

        for ($i = 0; $i < 8; $i++) {
            $customer = $customers[$customerKeys[$i % count($customerKeys)]];
            $saleType = match (true) {
                $customer->type?->slug === 'distributor' => $distributorSale,
                $customer->type?->slug === 'individual' => $retail,
                default => $wholesale,
            };
            $productA = $variants[$productKeys[$i % count($productKeys)]];
            $productB = $variants[$productKeys[($i + 3) % count($productKeys)]];
            $qtyA = 2 + ($i % 4);
            $qtyB = 2 + ($i % 3);
            $method = $paymentMethods[$i % count($paymentMethods)];

            $priceField = match ($saleType->slug) {
                'wholesale' => 'wholesale_price',
                'distributor' => 'distributor_price',
                default => 'retail_price',
            };
            $total = bcadd(
                bcmul((string) $qtyA, (string) $productA->{$priceField}, 2),
                bcmul((string) $qtyB, (string) $productB->{$priceField}, 2),
                2
            );

            app(SalesService::class)->createSale([
                'customer_id' => $customer->id,
                'sale_type_id' => $saleType->id,
                'sale_date' => $start->copy()->addDays(3 + $i)->format('Y-m-d H:i:s'),
                'notes' => self::MARKER,
                'items' => [
                    ['product_variant_id' => $productA->id, 'quantity' => (string) $qtyA, 'discount_amount' => '0'],
                    ['product_variant_id' => $productB->id, 'quantity' => (string) $qtyB, 'discount_amount' => '0'],
                ],
                'payments' => [
                    ['payment_method' => $method, 'amount' => $total, 'transaction_reference' => $method === 'MoMo' ? 'DEMO2-MOMO-'.Str::random(8) : null],
                ],
            ], $actor);
        }
    }

    private function recentExpenses(User $actor): void
    {
        $category = ExpenseCategory::query()->where('slug', 'demo-fuel-and-delivery')->first()
            ?? ExpenseCategory::query()->updateOrCreate(
                ['slug' => 'demo-fuel-and-delivery'],
                ['name' => 'Demo Fuel and Delivery', 'description' => 'Demo fuel and delivery costs.', 'is_active' => true]
            );

        $lines = [
            [1, '70.00', 'Fuel for delivery van'],
            [3, '55.00', 'Fuel for delivery run'],
        ];

        foreach ($lines as [$daysAgo, $amount, $description]) {
            $expense = app(ExpenseService::class)->createExpense([
                'expense_category_id' => $category->id,
                'incurred_on' => now()->subDays($daysAgo)->toDateString(),
                'payment_method' => 'Cash',
                'amount' => $amount,
                'description' => self::MARKER.' - '.$description,
            ], $actor);

            app(ExpenseService::class)->approve($expense, $actor);
        }
    }

    private function broadcastExample(User $actor): void
    {
        app(BroadcastService::class)->createBroadcast([
            'name' => 'Demo New Flavors Broadcast',
            'channel' => 'email',
            'subject' => 'New flavors are here!',
            'body' => "Hi! We've added new flavors - Watermelon, Mixed Fruit, Tigernut, Guavapine and Greek Yoghurt. Come grab yours!",
            'purchase_history' => 'any',
        ], $actor);
    }
}
