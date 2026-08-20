<?php

namespace Database\Seeders;

use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;
use App\Models\UnitConversion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MaterialFoundationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Piece', 'symbol' => 'pcs', 'type' => 'count'],
        ] as $unit) {
            MeasurementUnit::query()->updateOrCreate(['symbol' => $unit['symbol']], $unit);
        }

        foreach ([
            ['name' => 'Raw Materials', 'type' => 'raw_material', 'description' => 'Configurable raw-material categories and items.'],
            ['name' => 'Packaging Materials', 'type' => 'packaging_material', 'description' => 'Configurable packaging-material categories and items.'],
        ] as $category) {
            MaterialCategory::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                $category
            );
        }

        $this->conversion('kg', 'g', '1000');
        $this->conversion('L', 'ml', '1000');
    }

    private function conversion(string $from, string $to, string $factor): void
    {
        $fromUnit = MeasurementUnit::query()->where('symbol', $from)->first();
        $toUnit = MeasurementUnit::query()->where('symbol', $to)->first();

        if (! $fromUnit || ! $toUnit) {
            return;
        }

        UnitConversion::query()->updateOrCreate(
            ['from_unit_id' => $fromUnit->id, 'to_unit_id' => $toUnit->id],
            ['factor' => $factor]
        );
    }
}
