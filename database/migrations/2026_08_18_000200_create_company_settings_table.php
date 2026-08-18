<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('trading_name')->default("Ivy's Food");
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->string('time_zone')->default('Africa/Accra');
            $table->text('receipt_footer')->nullable();
            $table->string('tax_registration_info')->nullable();
            $table->unsignedSmallInteger('default_credit_period_days')->default(30);
            $table->unsignedInteger('default_low_stock_threshold')->nullable();
            $table->unsignedSmallInteger('default_expiry_alert_days')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
