<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\ExpenseCategory;
use App\Models\FinishedProductBatch;
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
use App\Services\CreditService;
use App\Services\ExpenseService;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\RecipeProductionService;
use App\Services\SalesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataExpansionSeeder extends Seeder
{
    private const MARKER = 'IVY_DEMO_EXPANSION';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('Demo data seeding is disabled in production.');

            return;
        }

        if ($this->shouldSkip()) {
            $this->command?->warn('Demo expansion data already exists. Nothing new was added.');

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
            $categories = $this->expenseCategories();

            $recipes = [];
            $cycles = [28, 21, 14, 7];

            foreach ($cycles as $daysAgo) {
                $start = now()->subDays($daysAgo);
                $this->restock($supplier, $materials, $units, $start, $actor);
                $recipes = $this->produce($variants, $materials, $units, $recipes, $start, $actor);
                $this->weeklySales($variants, $customers, $start, $actor);
            }

            $this->expenses($categories, $actor);
            $this->creditAgingExamples($variants, $customers, $actor);
            $this->stocktakeExample($actor);
            $this->broadcastExample($actor);
        });

        $this->command?->info('Demo data expanded for a fuller presentation.');
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

        return [
            'pineapple' => Material::query()->where('sku', 'DEMO-RM-PINEAPPLE')->firstOrFail(),
            'mango' => Material::query()->where('sku', 'DEMO-RM-MANGO')->firstOrFail(),
            'milk' => Material::query()->where('sku', 'DEMO-RM-MILK')->firstOrFail(),
            'sugar' => Material::query()->where('sku', 'DEMO-RM-SUGAR')->firstOrFail(),
            'bottle500' => Material::query()->where('sku', 'DEMO-PK-BOTTLE-500')->firstOrFail(),
            'orange' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-ORANGE'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Orange Concentrate',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '20.000',
                    'notes' => self::MARKER,
                ]
            ),
            'sobolo' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-SOBOLO'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Sobolo Concentrate',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '15.000',
                    'notes' => self::MARKER,
                ]
            ),
            'strawberry' => Material::query()->updateOrCreate(
                ['sku' => 'DEMO-RM-STRAWBERRY'],
                [
                    'material_category_id' => $raw->id,
                    'base_unit_id' => $units['kg']->id,
                    'name' => 'Demo Strawberry Pulp',
                    'status' => 'active',
                    'track_lot' => true,
                    'track_expiry' => true,
                    'reorder_level' => '15.000',
                    'notes' => self::MARKER,
                ]
            ),
        ];
    }

    private function supplier(): Supplier
    {
        return Supplier::query()->where('email', 'demo-supplier@ivys-food.test')->firstOrFail();
    }

    /**
     * @return array<string, ProductVariant>
     */
    private function products(): array
    {
        $existing = collect([
            'pineapple' => 'Pineapple 500ml',
            'mango' => 'Mango 500ml',
            'vanilla' => 'Vanilla 500ml',
        ])->mapWithKeys(fn (string $name, string $key): array => [$key => ProductVariant::query()->where('name', $name)->firstOrFail()]);

        $new = [
            'orange' => ['Orange 500ml', 'DEMO-JUICE-ORANGE-500', '7.00', '6.00', '5.50', '80.000', 14],
            'sobolo' => ['Sobolo 500ml', 'DEMO-JUICE-SOBOLO-500', '6.50', '5.50', '5.00', '60.000', 14],
            'strawberry' => ['Strawberry 500ml', 'DEMO-YOG-STRAW-500', '10.00', '9.00', '8.00', '50.000', 10],
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
    private function customers(): array
    {
        $individual = CustomerType::query()->where('slug', 'individual')->firstOrFail();
        $shop = CustomerType::query()->where('slug', 'shop')->firstOrFail();
        $distributorType = CustomerType::query()->where('slug', 'distributor')->firstOrFail();
        $mall = CustomerType::query()->where('slug', 'mall-supermarket')->firstOrFail();

        $retail = SaleType::query()->where('slug', 'retail')->firstOrFail();
        $wholesale = SaleType::query()->where('slug', 'wholesale')->firstOrFail();
        $distributorSale = SaleType::query()->where('slug', 'distributor')->firstOrFail();

        $existing = collect([
            'walkin' => '233240001001',
            'shop' => '233240001002',
            'distributor' => '233240001003',
        ])->mapWithKeys(fn (string $phone, string $key): array => [$key => Customer::query()->where('normalized_phone', $phone)->firstOrFail()]);

        $definitions = [
            'ama' => [$individual->id, $retail->id, 'Demo Ama Serwaa', 'Ama Serwaa', '0240001004', 'East Legon', '1004'],
            'kwame' => [$individual->id, $retail->id, 'Demo Kwame Owusu', 'Kwame Owusu', '0240001005', 'Achimota', '1005'],
            'spintex' => [$shop->id, $wholesale->id, 'Demo Spintex Provisions', 'Yaw Demo', '0240001006', 'Spintex', '1006'],
            'dzorwulu' => [$shop->id, $wholesale->id, 'Demo Dzorwulu Grocery', 'Abena Demo', '0240001007', 'Dzorwulu', '1007'],
            'kumasi' => [$distributorType->id, $distributorSale->id, 'Demo Kumasi Distributor', 'Kofi Demo', '0240001008', 'Kumasi', '1008'],
            'palace' => [$mall->id, $wholesale->id, 'Demo Palace Mall Outlet', 'Efua Demo', '0240001009', 'Osu', '1009'],
        ];

        $new = collect($definitions)->mapWithKeys(function (array $data, string $key): array {
            [$typeId, $saleTypeId, $name, $contact, $phone, $location, $suffix] = $data;

            return [$key => Customer::query()->updateOrCreate(
                ['normalized_phone' => '233'.substr($phone, 1)],
                [
                    'customer_type_id' => $typeId,
                    'default_sale_type_id' => $saleTypeId,
                    'name' => $name,
                    'contact_person' => $contact,
                    'phone' => $phone,
                    'email' => 'demo-'.Str::slug($key).'@ivys-food.test',
                    'location' => $location,
                    'address' => $location.' area',
                    'status' => 'active',
                    'credit_limit' => '400.00',
                    'outstanding_balance' => '0.00',
                    'communication_consent' => true,
                    'notes' => self::MARKER,
                ]
            )];
        });

        return $existing->merge($new)->all();
    }

    /**
     * @return array<string, ExpenseCategory>
     */
    private function expenseCategories(): array
    {
        $names = [
            'demo-rent-and-utilities' => 'Demo Rent and Utilities',
            'demo-salaries-and-wages' => 'Demo Salaries and Wages',
            'demo-packaging-and-supplies' => 'Demo Packaging and Supplies',
            'demo-vehicle-maintenance' => 'Demo Vehicle Maintenance',
        ];

        return collect($names)->mapWithKeys(fn (string $name, string $slug): array => [$slug => ExpenseCategory::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'description' => $name.' (demo).', 'is_active' => true]
        )])->all();
    }

    /**
     * @param  array<string, Material>  $materials
     * @param  array<string, MeasurementUnit>  $units
     */
    private function restock(Supplier $supplier, array $materials, array $units, Carbon $start, User $actor): void
    {
        $purchase = app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'purchase_date' => $start->toDateString(),
            'notes' => self::MARKER,
            'items' => [
                ['material_id' => $materials['pineapple']->id, 'unit_id' => $units['kg']->id, 'quantity' => '60', 'unit_cost' => '5.5000'],
                ['material_id' => $materials['mango']->id, 'unit_id' => $units['kg']->id, 'quantity' => '60', 'unit_cost' => '7.0000'],
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'quantity' => '200', 'unit_cost' => '4.0000'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'quantity' => '40', 'unit_cost' => '6.5000'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'quantity' => '700', 'unit_cost' => '0.8000'],
                ['material_id' => $materials['orange']->id, 'unit_id' => $units['kg']->id, 'quantity' => '35', 'unit_cost' => '6.0000'],
                ['material_id' => $materials['sobolo']->id, 'unit_id' => $units['kg']->id, 'quantity' => '35', 'unit_cost' => '5.0000'],
                ['material_id' => $materials['strawberry']->id, 'unit_id' => $units['kg']->id, 'quantity' => '15', 'unit_cost' => '9.0000'],
            ],
        ], $actor);

        app(PurchaseService::class)->receive($purchase, [
            'received_date' => $start->copy()->addDay()->toDateString(),
            'batches' => $purchase->items->map(fn ($item): array => [
                'purchase_item_id' => $item->id,
                'lot_number' => 'DEMO-LOT-'.$item->id,
                'expiry_date' => $start->copy()->addMonths(4)->toDateString(),
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
            'pineapple' => [
                ['material_id' => $materials['pineapple']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '30'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'mango' => [
                ['material_id' => $materials['mango']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '35'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '4'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'vanilla' => [
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'usage_type' => 'ingredient', 'quantity' => '80'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '6'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'orange' => [
                ['material_id' => $materials['orange']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '28'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'sobolo' => [
                ['material_id' => $materials['sobolo']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '28'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '5'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
            'strawberry' => [
                ['material_id' => $materials['milk']->id, 'unit_id' => $units['L']->id, 'usage_type' => 'ingredient', 'quantity' => '75'],
                ['material_id' => $materials['strawberry']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '10'],
                ['material_id' => $materials['sugar']->id, 'unit_id' => $units['kg']->id, 'usage_type' => 'ingredient', 'quantity' => '6'],
                ['material_id' => $materials['bottle500']->id, 'unit_id' => $units['pcs']->id, 'usage_type' => 'packaging', 'quantity' => '100'],
            ],
        ];

        $shelfDays = ['pineapple' => 14, 'mango' => 14, 'vanilla' => 10, 'orange' => 14, 'sobolo' => 14, 'strawberry' => 10];

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
    private function weeklySales(array $variants, array $customers, Carbon $start, User $actor): void
    {
        $retail = SaleType::query()->where('slug', 'retail')->firstOrFail();
        $wholesale = SaleType::query()->where('slug', 'wholesale')->firstOrFail();
        $distributorSale = SaleType::query()->where('slug', 'distributor')->firstOrFail();

        $customerKeys = array_keys($customers);
        $productKeys = array_keys($variants);
        $paymentMethods = ['Cash', 'MoMo'];

        for ($i = 0; $i < 6; $i++) {
            $customer = $customers[$customerKeys[$i % count($customerKeys)]];
            $saleType = match (true) {
                $customer->type?->slug === 'distributor' => $distributorSale,
                $customer->type?->slug === 'individual' => $retail,
                default => $wholesale,
            };
            $productA = $variants[$productKeys[$i % count($productKeys)]];
            $productB = $variants[$productKeys[($i + 2) % count($productKeys)]];
            $qtyA = 3 + ($i % 4);
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

            $sale = app(SalesService::class)->createSale([
                'customer_id' => $customer->id,
                'sale_type_id' => $saleType->id,
                'sale_date' => $start->copy()->addDays(3 + $i)->format('Y-m-d H:i:s'),
                'notes' => self::MARKER,
                'items' => [
                    ['product_variant_id' => $productA->id, 'quantity' => (string) $qtyA, 'discount_amount' => '0'],
                    ['product_variant_id' => $productB->id, 'quantity' => (string) $qtyB, 'discount_amount' => '0'],
                ],
                'payments' => [
                    ['payment_method' => $method, 'amount' => $total, 'transaction_reference' => $method === 'MoMo' ? 'DEMO-MOMO-'.Str::random(8) : null],
                ],
            ], $actor);
        }
    }

    /**
     * @param  array<string, ExpenseCategory>  $categories
     */
    private function expenses(array $categories, User $actor): void
    {
        $lines = [
            ['demo-fuel-and-delivery', 28, '90.00', 'Fuel for delivery van'],
            ['demo-rent-and-utilities', 25, '450.00', 'Monthly shop rent'],
            ['demo-salaries-and-wages', 21, '620.00', 'Staff wages'],
            ['demo-packaging-and-supplies', 18, '135.00', 'Bottles and labels top-up'],
            ['demo-vehicle-maintenance', 15, '210.00', 'Delivery van service'],
            ['demo-fuel-and-delivery', 14, '95.00', 'Fuel for delivery van'],
            ['demo-rent-and-utilities', 11, '80.00', 'Electricity bill'],
            ['demo-packaging-and-supplies', 8, '60.00', 'Printed receipt rolls'],
            ['demo-salaries-and-wages', 5, '150.00', 'Casual labour for production day'],
            ['demo-fuel-and-delivery', 2, '75.00', 'Fuel for delivery van'],
        ];

        $category = ExpenseCategory::query()->where('slug', 'demo-fuel-and-delivery')->firstOrFail();
        $categories = array_merge(['demo-fuel-and-delivery' => $category], $categories);

        foreach ($lines as [$slug, $daysAgo, $amount, $description]) {
            $expense = app(ExpenseService::class)->createExpense([
                'expense_category_id' => $categories[$slug]->id,
                'incurred_on' => now()->subDays($daysAgo)->toDateString(),
                'payment_method' => 'Cash',
                'amount' => $amount,
                'description' => self::MARKER.' - '.$description,
            ], $actor);

            app(ExpenseService::class)->approve($expense, $actor);
        }
    }

    /**
     * @param  array<string, ProductVariant>  $variants
     * @param  array<string, Customer>  $customers
     */
    private function creditAgingExamples(array $variants, array $customers, User $actor): void
    {
        $retail = SaleType::query()->where('slug', 'retail')->firstOrFail();
        $wholesale = SaleType::query()->where('slug', 'wholesale')->firstOrFail();
        $distributorSale = SaleType::query()->where('slug', 'distributor')->firstOrFail();

        $cases = [
            ['walkin', $retail, 3, 27, null],
            ['shop', $wholesale, 25, -5, null],
            ['distributor', $distributorSale, 40, -10, null],
            ['ama', $retail, 65, -35, '40.00'],
            ['spintex', $wholesale, 90, -60, null],
            ['palace', $wholesale, 20, 10, 'full'],
        ];

        foreach ($cases as [$customerKey, $saleType, $issuedDaysAgo, $dueOffsetDays, $paymentAction]) {
            $customer = $customers[$customerKey];
            $saleDate = now()->subDays($issuedDaysAgo);

            $sale = app(SalesService::class)->createSale([
                'customer_id' => $customer->id,
                'sale_type_id' => $saleType->id,
                'sale_date' => $saleDate->format('Y-m-d H:i:s'),
                'notes' => self::MARKER,
                'items' => [
                    ['product_variant_id' => $variants['pineapple']->id, 'quantity' => '10', 'discount_amount' => '0'],
                ],
                'payments' => [
                    ['payment_method' => 'Credit', 'amount' => '0.00'],
                ],
            ], $actor);

            $invoice = $sale->creditInvoice;
            $invoice->update([
                'issue_date' => $saleDate->toDateString(),
                'due_date' => now()->addDays($dueOffsetDays)->toDateString(),
            ]);

            if ($paymentAction === 'full') {
                app(CreditService::class)->recordPayment($customer->fresh(), [
                    'payment_method' => 'MoMo',
                    'amount' => (string) $invoice->balance,
                    'transaction_reference' => 'DEMO-CREDIT-PAY-'.$customer->id,
                    'received_at' => now(),
                ], $actor);
            } elseif (is_string($paymentAction)) {
                app(CreditService::class)->recordPayment($customer->fresh(), [
                    'payment_method' => 'MoMo',
                    'amount' => $paymentAction,
                    'transaction_reference' => 'DEMO-CREDIT-PAY-'.$customer->id,
                    'received_at' => now(),
                ], $actor);
            }
        }

        app(CreditService::class)->refreshStatuses();
    }

    private function stocktakeExample(User $actor): void
    {
        $batch = FinishedProductBatch::query()
            ->where('quantity_available', '>', 5)
            ->orderByDesc('id')
            ->first();

        if (! $batch) {
            return;
        }

        $counted = (string) max(0, (int) $batch->quantity_available - 2);

        $stocktake = app(InventoryService::class)->createStocktake([
            'stock_type' => 'finished',
            'stocktake_date' => now()->toDateString(),
            'notes' => self::MARKER,
            'items' => [
                ['batch_id' => $batch->id, 'counted_quantity' => $counted, 'notes' => 'Demo shelf count'],
            ],
        ], $actor);

        app(InventoryService::class)->approveStocktake($stocktake, $actor);
    }

    private function broadcastExample(User $actor): void
    {
        app(BroadcastService::class)->createBroadcast([
            'name' => 'Demo Weekly Promo Broadcast',
            'channel' => 'email',
            'subject' => 'This week at Ivy\'s Food',
            'body' => 'Hi! Fresh batches of juice and yoghurt are ready this week. Visit us or place an order.',
        ], $actor);
    }
}
