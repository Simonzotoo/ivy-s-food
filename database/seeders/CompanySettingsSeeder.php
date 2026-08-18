<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        CompanySetting::query()->firstOrCreate([], [
            'company_name' => null,
            'trading_name' => "Ivy's Food",
            'logo_path' => 'images/ivys-logo.png',
            'address' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
            'currency' => 'GHS',
            'time_zone' => 'Africa/Accra',
            'receipt_footer' => null,
            'tax_registration_info' => null,
            'default_credit_period_days' => 30,
            'default_low_stock_threshold' => null,
            'default_expiry_alert_days' => null,
        ]);
    }
}
