<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use App\Models\SaleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerFoundationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Individual', 'Shop', 'Distributor', 'Mall / Supermarket', 'Organization'] as $name) {
            CustomerType::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_system' => true]
            );
        }

        foreach (['Retail', 'Wholesale', 'Distributor'] as $name) {
            SaleType::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_system' => true]
            );
        }
    }
}
