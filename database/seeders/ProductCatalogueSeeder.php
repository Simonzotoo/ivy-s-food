<?php

namespace Database\Seeders;

use App\Models\MeasurementUnit;
use App\Models\ProductCategory;
use App\Models\ProductSize;
use App\Models\ProductType;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCatalogueSeeder extends Seeder
{
    private const CATALOGUE = [
        'Fruit Juice' => [
            'description' => 'Fruit juice products.',
            'sizes' => ['250ml', '350ml', '500ml', '2L', '5L'],
            'types' => [
                'Pineapple',
                'Pinegin',
                'Pinemint',
                'Coropine',
                'Pineginbeet',
                'Guavapine',
                'Watermelon',
                'Melonginbeet',
                'Mango',
                'Orange',
                'Mixed Fruit',
                'Sobolo',
                'Tigernut',
                'Tigernut Beet',
            ],
        ],
        'Yoghurt' => [
            'description' => 'Yoghurt products.',
            'sizes' => ['250ml', '330ml', '500ml', '2L', '5L'],
            'types' => [
                'Vanilla',
                'Strawberry',
                'Banana',
                'Vanilla Wheat',
                'Strawberry Wheat',
                'Banana Wheat',
                'Vanilla Coconut',
                'Strawberry Coconut',
                'Banana Coconut',
            ],
        ],
        'Greek Yoghurt' => [
            'description' => 'Greek yoghurt products.',
            'sizes' => ['500g', '1000g', '4kg'],
            'types' => [
                'Greek Yoghurt Unsweetened',
                'Greek Yoghurt Sweetened',
                'Greek Plus Wheat Sweetened',
                'Greek Plus Wheat Unsweetened',
                'Greek Plus Coconut Sweetened',
                'Greek Plus Coconut Unsweetened',
            ],
        ],
    ];

    public function run(): void
    {
        $units = [
            'ml' => ['Millilitre', 'volume'],
            'L' => ['Litre', 'volume'],
            'g' => ['Gram', 'weight'],
            'kg' => ['Kilogram', 'weight'],
        ];

        foreach ($units as $symbol => [$name, $type]) {
            MeasurementUnit::query()->updateOrCreate(
                ['symbol' => $symbol],
                ['name' => $name, 'type' => $type]
            );
        }

        $categoryOrder = 1;
        foreach (self::CATALOGUE as $categoryName => $definition) {
            $category = ProductCategory::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'description' => $definition['description'],
                    'sort_order' => $categoryOrder++,
                ]
            );

            $sizes = [];
            foreach ($definition['sizes'] as $index => $label) {
                [$quantity, $symbol] = $this->parseSize($label);
                $unit = MeasurementUnit::query()->where('symbol', $symbol)->firstOrFail();
                $sizes[] = ProductSize::query()->updateOrCreate(
                    ['product_category_id' => $category->id, 'label' => $label],
                    [
                        'measurement_unit_id' => $unit->id,
                        'quantity' => $quantity,
                        'sort_order' => $index + 1,
                    ]
                );
            }

            foreach ($definition['types'] as $index => $typeName) {
                $type = ProductType::query()->updateOrCreate(
                    ['product_category_id' => $category->id, 'name' => $typeName],
                    [
                        'slug' => Str::slug($typeName),
                        'status' => 'active',
                        'sort_order' => $index + 1,
                    ]
                );

                foreach ($sizes as $size) {
                    ProductVariant::query()->firstOrCreate(
                        ['product_type_id' => $type->id, 'product_size_id' => $size->id],
                        [
                            'product_category_id' => $category->id,
                            'name' => "{$typeName} {$size->label}",
                            'status' => 'draft',
                        ]
                    );
                }
            }
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseSize(string $label): array
    {
        preg_match('/^([0-9]+(?:\.[0-9]+)?)(ml|L|g|kg)$/', $label, $matches);

        return [$matches[1], $matches[2]];
    }
}
