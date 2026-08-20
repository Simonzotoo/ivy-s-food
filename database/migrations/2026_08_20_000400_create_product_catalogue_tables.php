<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('symbol', 20)->unique();
            $table->string('type', 30)->index();
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('status', 20)->default('active')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_category_id', 'name']);
            $table->unique(['product_category_id', 'slug']);
        });

        Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measurement_unit_id')->constrained()->restrictOnDelete();
            $table->string('label');
            $table->decimal('quantity', 12, 3);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_category_id', 'label']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_size_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('retail_price', 12, 2)->nullable();
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->decimal('distributor_price', 12, 2)->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('reorder_level', 12, 3)->nullable();
            $table->unsignedSmallInteger('shelf_life_days')->nullable();
            $table->timestamps();

            $table->unique(['product_type_id', 'product_size_id']);
            $table->index(['product_category_id', 'status']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_sizes');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('measurement_units');
    }
};
