<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'trading_name',
        'logo_path',
        'address',
        'phone',
        'email',
        'website',
        'currency',
        'time_zone',
        'receipt_footer',
        'tax_registration_info',
        'default_credit_period_days',
        'default_low_stock_threshold',
        'default_expiry_alert_days',
    ];

    protected function casts(): array
    {
        return [
            'default_credit_period_days' => 'integer',
            'default_low_stock_threshold' => 'integer',
            'default_expiry_alert_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'trading_name' => "Ivy's Food",
            'currency' => 'GHS',
            'time_zone' => 'Africa/Accra',
            'default_credit_period_days' => 30,
            'logo_path' => 'images/ivys-logo.png',
        ]);
    }
}
